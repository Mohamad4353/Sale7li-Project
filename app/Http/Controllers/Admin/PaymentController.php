<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RejectWhishPaymentRequest;
use App\Models\Notification;
use App\Models\Payment;
use App\Payments\Money;
use App\Payments\PaymentCaptureService;
use App\Payments\ReconciliationService;
use App\Payments\RefundService;
use App\Support\CsvExport;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Read-only admin financial console. Lists persisted Payment records (the
 * financial ledger for this phase) with a summary strip. All totals come from
 * SQL aggregation over the same filter set — never from iterating rows in PHP,
 * and never from the live commission rate.
 */
class PaymentController extends Controller
{
    private const STATUSES = ['pending', 'paid', 'failed', 'refunded', 'partially_refunded'];

    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $apply = fn ($query) => $this->applyFilters($query, $filters);

        $summary = $apply(Payment::query()->where('status', 'paid'))
            ->selectRaw('COUNT(*) as paid_count')
            ->selectRaw('COALESCE(SUM(amount), 0) as gross')
            ->selectRaw('COALESCE(SUM(commission_amount), 0) as commission')
            ->selectRaw('COALESCE(SUM(worker_net), 0) as net')
            ->selectRaw('COALESCE(AVG(amount), 0) as average')
            ->first();

        $payments = $apply(
            Payment::query()->with(['request:id,reference,service_category_id', 'request.category:id,name,name_ar', 'customer:id,name', 'worker:id,name', 'refunds'])
        )->latest('paid_at')->latest('id')->paginate(20)->withQueryString();

        $refundMap = $this->refundMap($payments->getCollection(), app(RefundService::class));

        $whishPending = Payment::query()
            ->where('payment_method', 'whish')
            ->where('status', 'pending')
            ->count();

        return view('admin.payments.index', compact('payments', 'summary', 'filters', 'refundMap', 'whishPending') + ['statuses' => self::STATUSES]);
    }

    /**
     * Read-only financial reconciliation across payments, invoices, requests and
     * refunds. Never mutates a record — surfacing a mismatch is the whole job.
     */
    public function reconciliation(ReconciliationService $reconciliation)
    {
        return view('admin.payments.reconciliation', ['report' => $reconciliation->report()]);
    }

    /**
     * CSV of the payments ledger for the current filter set — the same query
     * that backs index(), just streamed row by row instead of paginated.
     */
    public function export(Request $request, RefundService $refunds)
    {
        $filters = $this->filters($request);

        $query = $this->applyFilters(
            Payment::query()->with(['request:id,reference,service_category_id', 'request.category:id,name,name_ar', 'customer:id,name', 'worker:id,name', 'refunds']),
            $filters,
        )->latest('paid_at')->latest('id');

        $headings = [
            __('payments.transaction'),
            __('payments.col_paid_date'),
            __('payments.col_customer'),
            __('payments.col_worker'),
            __('payments.col_service'),
            __('payments.col_gross'),
            __('payments.col_rate'),
            __('payments.col_commission'),
            __('payments.col_net'),
            __('payments.col_status'),
            __('payments.col_refunded'),
        ];

        $rows = function () use ($query, $refunds) {
            foreach ($query->lazy() as $payment) {
                $refundedCents = $refunds->refundedCents($payment);

                yield [
                    $payment->transaction_reference,
                    $payment->paid_at?->toDateString(),
                    $payment->customer?->name,
                    $payment->worker?->name,
                    $payment->request?->category?->localized_name,
                    $payment->amount,
                    $payment->commission_rate,
                    $payment->commission_amount,
                    $payment->worker_net,
                    __('status.payment.'.$payment->status),
                    $refundedCents > 0 ? Money::toDecimalString($refundedCents) : '',
                ];
            }
        };

        return CsvExport::download('payments-'.now()->toDateString().'.csv', $headings, $rows());
    }

    /**
     * CSV of every discrepancy the reconciliation pass detected. No filters —
     * mirrors the reconciliation() screen exactly.
     */
    public function reconciliationExport(ReconciliationService $reconciliation)
    {
        $report = $reconciliation->report();

        $headings = [
            __('payments.recon_payment_ref'),
            __('payments.recon_col_issue'),
            __('requests.reference'),
            __('payments.col_customer'),
            __('payments.col_worker'),
            __('payments.recon_col_expected'),
            __('payments.recon_col_actual'),
        ];

        $rows = array_map(fn ($d) => [
            $d->paymentId,
            $d->label(),
            $d->reference,
            $d->customer,
            $d->worker,
            $d->expected,
            $d->actual,
        ], $report->discrepancies);

        return CsvExport::download('reconciliation-'.now()->toDateString().'.csv', $headings, $rows);
    }

    /** The review queue: every WHISH submission still awaiting a verdict. */
    public function pendingWhish()
    {
        $payments = Payment::query()
            ->where('payment_method', 'whish')
            ->where('status', 'pending')
            ->with([
                'customer:id,name,email',
                'worker:id,name',
                'request:id,reference,service_category_id',
                'request.category:id,name,name_ar',
            ])
            ->oldest('submitted_at')
            ->paginate(20);

        return view('admin.payments.whish', compact('payments'));
    }

    /**
     * Approve a pending WHISH submission. Settlement runs through the shared
     * PaymentCaptureService, so commission, worker-net and the invoice are
     * computed exactly as the instant flow computes them — the invariant
     * quotation.total == invoice.total == payment.amount == final_total holds.
     */
    public function approveWhish(Payment $payment, PaymentCaptureService $capture)
    {
        $this->authorize('verify', $payment);
        abort_unless($payment->isWhishPending(), 422);

        $serviceRequest = $payment->request;
        abort_if($serviceRequest === null || $serviceRequest->payment_status === 'paid', 422);

        $settled = $capture->capture($serviceRequest, [
            'verified_at' => now(),
            'verified_by' => auth()->id(),
            'rejection_reason' => null,
        ]);

        Notification::record($payment->customer_id, 'whish_payment_approved', $serviceRequest->id, [
            'reference' => $serviceRequest->reference,
            'amount' => Money::format($settled->amount),
        ]);

        if ($serviceRequest->worker_id) {
            Notification::record($serviceRequest->worker_id, 'payment_confirmed', $serviceRequest->id, [
                'reference' => $serviceRequest->reference,
            ]);
        }

        return back()->with('success', __('messages.flash.whish_approved'));
    }

    /**
     * Reject a pending WHISH submission. The service request is NOT marked paid;
     * the customer can submit a fresh WHISH payment afterwards.
     */
    public function rejectWhish(RejectWhishPaymentRequest $request, Payment $payment)
    {
        $this->authorize('verify', $payment);
        abort_unless($payment->isWhishPending(), 422);

        $payment->update([
            'status' => 'failed',
            'rejection_reason' => $request->validated()['reason'] ?? null,
            'verified_at' => now(),
            'verified_by' => auth()->id(),
        ]);

        Notification::record($payment->customer_id, 'whish_payment_rejected', $payment->service_request_id, [
            'reference' => $payment->request?->reference,
        ]);

        return back()->with('success', __('messages.flash.whish_rejected'));
    }

    /** @return array{status: ?string, worker: ?string, customer: ?string, from: ?string, to: ?string} */
    private function filters(Request $request): array
    {
        return [
            'status' => in_array($request->query('status'), self::STATUSES, true) ? $request->query('status') : null,
            'worker' => trim((string) $request->query('worker')) ?: null,
            'customer' => trim((string) $request->query('customer')) ?: null,
            'from' => $this->date($request->query('from')),
            'to' => $this->date($request->query('to')),
        ];
    }

    /**
     * @param  array{status: ?string, worker: ?string, customer: ?string, from: ?string, to: ?string}  $filters
     */
    private function applyFilters($query, array $filters)
    {
        return $query
            ->when($filters['status'], fn ($q) => $q->where('status', $filters['status']))
            ->when($filters['worker'], fn ($q) => $q->whereHas('worker', fn ($w) => $w->where('name', 'like', '%'.$filters['worker'].'%')))
            ->when($filters['customer'], fn ($q) => $q->whereHas('customer', fn ($c) => $c->where('name', 'like', '%'.$filters['customer'].'%')))
            ->when($filters['from'], fn ($q) => $q->whereDate('paid_at', '>=', $filters['from']))
            ->when($filters['to'], fn ($q) => $q->whereDate('paid_at', '<=', $filters['to']));
    }

    /**
     * Per-row refund display data, derived from the already eager-loaded
     * `refunds` relation (no extra queries) via the refund service — never
     * recomputed in Blade.
     *
     * @param  Collection<int, Payment>  $payments
     * @return array<int, array{refunded: ?string, remaining: ?string, is_refunded: bool}>
     */
    private function refundMap($payments, RefundService $refunds): array
    {
        $map = [];

        foreach ($payments as $payment) {
            $refundedCents = $refunds->refundedCents($payment);
            $isRefunded = $refunds->isFullyRefunded($payment);

            $map[$payment->id] = [
                'refunded' => $refundedCents > 0 ? Money::format(Money::toDecimalString($refundedCents)) : null,
                'remaining' => $isRefunded && $payment->amount !== null
                    ? Money::format(Money::toDecimalString(max(0, Money::toCents($payment->amount) - $refundedCents)))
                    : null,
                'is_refunded' => $isRefunded,
            ];
        }

        return $map;
    }

    private function date(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
