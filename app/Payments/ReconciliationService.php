<?php

namespace App\Payments;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Refund;
use App\Payments\Reconciliation\Discrepancy;
use App\Payments\Reconciliation\ReconciliationReport;
use Illuminate\Support\Collection;

/**
 * Read-only financial consistency check across payments, invoices, requests and
 * refunds. It never writes — running it does not "repair" anything.
 *
 * Totals use SQL SUM/COUNT and integer-cent comparison. Historical commission is
 * read from the frozen Payment columns; the live platform rate is never used.
 *
 * Every probe is a bounded aggregate/exists query (no Payment::all() + loop),
 * and one final query hydrates just the flagged rows for display.
 */
class ReconciliationService
{
    /** @var Collection<int, int>|null memoised completed-refund cents per payment id */
    private ?Collection $refundSums = null;

    public function report(): ReconciliationReport
    {
        $checked = Payment::query()->where('status', 'paid')->count();

        // Corrupt negative rows are reported as discrepancies, not folded into
        // the headline totals — guard the SUMs so one bad row can't poison them
        // or throw in the non-negative money parser.
        $grossCents = Money::toCents((string) Payment::query()
            ->where('status', 'paid')->where('amount', '>=', 0)->sum('amount'));
        $refundedCents = Money::toCents((string) Refund::query()
            ->where('status', Refund::STATUS_COMPLETED)->where('amount', '>=', 0)->sum('amount'));
        $retainedCents = max(0, $grossCents - $refundedCents);

        $refundedPayments = (int) Refund::query()
            ->where('status', Refund::STATUS_COMPLETED)
            ->distinct()
            ->count('payment_id');

        [$discrepancies, $flaggedPaidIds] = $this->detect();

        // `checked` counts only paid payments, so only paid flagged payments may
        // be subtracted from it. A corrupt non-paid row is still listed as a
        // discrepancy but must not dent the reconciled-of-checked figure.
        $reconciled = max(0, $checked - $flaggedPaidIds->count());

        return new ReconciliationReport(
            checked: $checked,
            reconciled: $reconciled,
            refundedPayments: $refundedPayments,
            grossCaptured: Money::toDecimalString($grossCents),
            totalRefunded: Money::toDecimalString($refundedCents),
            retained: Money::toDecimalString($retainedCents),
            discrepancies: $discrepancies,
        );
    }

    /**
     * @return array{0: array<int, Discrepancy>, 1: Collection<int, int>}
     */
    private function detect(): array
    {
        // code => list of payment ids
        $byCode = [
            'missing_invoice' => $this->missingInvoice(),
            'invoice_total_mismatch' => $this->columnMismatch('invoices', 'total'),
            'final_total_mismatch' => $this->finalTotalMismatch(),
            'over_refund' => $this->overRefund(),
            'full_refund_amount_mismatch' => $this->fullRefundAmountMismatch(),
            'duplicate_refund' => $this->duplicateRefund(),
            'negative_amount' => $this->negativeAmount(),
        ];

        $flaggedIds = collect($byCode)->flatten()->unique()->values();

        $paymentContext = Payment::query()
            ->with([
                'request:id,reference,final_total',
                'customer:id,name',
                'worker:id,name',
            ])
            ->whereIn('id', $flaggedIds)
            ->get()
            ->keyBy('id');

        // Only paid flagged payments count against `checked` (which is paid-only).
        // Non-paid corrupt rows — a garbage pending payment, an over-refund on a
        // failed payment — are still listed below, just not subtracted.
        $flaggedPaidIds = $paymentContext
            ->filter(fn (Payment $p) => $p->status === 'paid')
            ->keys()
            ->values();

        $discrepancies = [];

        foreach ($byCode as $code => $ids) {
            foreach ($ids as $paymentId) {
                $payment = $paymentContext->get($paymentId);
                $discrepancies[] = new Discrepancy(
                    code: $code,
                    paymentId: $paymentId,
                    reference: $payment?->request?->reference,
                    customer: $payment?->customer?->name,
                    worker: $payment?->worker?->name,
                    expected: $this->expectedFor($code, $payment),
                    actual: $this->actualFor($code, $payment),
                );
            }
        }

        // Refund-level problems that are not keyed by a valid paid payment.
        foreach ($this->negativeRefunds() as $refund) {
            $discrepancies[] = new Discrepancy(
                code: 'negative_refund',
                paymentId: $refund->payment_id,
                actual: Money::format($refund->amount),
            );
        }

        foreach ($this->orphanRefunds() as $refund) {
            $discrepancies[] = new Discrepancy(
                code: 'orphan_refund',
                paymentId: $refund->payment_id,
                actual: Money::format($refund->amount),
            );
        }

        return [$discrepancies, $flaggedPaidIds];
    }

    /** Paid payments with no invoice for their service request. */
    private function missingInvoice(): array
    {
        return Payment::query()
            ->where('status', 'paid')
            ->whereNotExists(fn ($q) => $q->from('invoices')
                ->whereColumn('invoices.service_request_id', 'payments.service_request_id'))
            ->pluck('id')
            ->all();
    }

    /** Paid payment amount != the joined column on invoices. */
    private function columnMismatch(string $table, string $column): array
    {
        return Payment::query()
            ->where('payments.status', 'paid')
            ->join($table, "$table.service_request_id", '=', 'payments.service_request_id')
            ->whereColumn("$table.$column", '!=', 'payments.amount')
            ->pluck('payments.id')
            ->all();
    }

    private function finalTotalMismatch(): array
    {
        return Payment::query()
            ->where('payments.status', 'paid')
            ->join('service_requests', 'service_requests.id', '=', 'payments.service_request_id')
            ->whereNotNull('service_requests.final_total')
            ->whereColumn('service_requests.final_total', '!=', 'payments.amount')
            ->pluck('payments.id')
            ->all();
    }

    /** Completed refunds on a payment summing to more than the payment amount. */
    private function overRefund(): array
    {
        return $this->refundSumsByPayment()
            ->filter(fn (int $refundCents, int $paymentId) => $refundCents > $this->paymentCents($paymentId))
            ->keys()
            ->all();
    }

    /** A full-type completed refund whose amount != its payment's amount. */
    private function fullRefundAmountMismatch(): array
    {
        return Refund::query()
            ->where('refunds.type', Refund::TYPE_FULL)
            ->where('refunds.status', Refund::STATUS_COMPLETED)
            ->join('payments', 'payments.id', '=', 'refunds.payment_id')
            ->whereColumn('refunds.amount', '!=', 'payments.amount')
            ->pluck('payments.id')
            ->all();
    }

    private function duplicateRefund(): array
    {
        return Refund::query()
            ->where('type', Refund::TYPE_FULL)
            ->where('status', Refund::STATUS_COMPLETED)
            ->groupBy('payment_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('payment_id')
            ->all();
    }

    private function negativeAmount(): array
    {
        $payments = Payment::query()->where('amount', '<', 0)->pluck('id');

        $fromInvoices = Payment::query()
            ->join('invoices', 'invoices.service_request_id', '=', 'payments.service_request_id')
            ->where('invoices.total', '<', 0)
            ->pluck('payments.id');

        return $payments->merge($fromInvoices)->unique()->values()->all();
    }

    /** @return Collection<int, Refund> */
    private function negativeRefunds(): Collection
    {
        return Refund::query()->where('amount', '<', 0)->get();
    }

    /** @return Collection<int, Refund> */
    private function orphanRefunds(): Collection
    {
        return Refund::query()
            ->whereNotExists(fn ($q) => $q->from('payments')->whereColumn('payments.id', 'refunds.payment_id'))
            ->get();
    }

    /**
     * Completed-refund totals in cents, keyed by payment id. Bounded by the
     * number of refunded payments, which is tiny relative to all payments.
     *
     * @return Collection<int, int>
     */
    private function refundSumsByPayment(): Collection
    {
        return $this->refundSums ??= Refund::query()
            ->where('status', Refund::STATUS_COMPLETED)
            ->where('amount', '>=', 0)
            ->groupBy('payment_id')
            ->selectRaw('payment_id, SUM(amount) as total')
            ->pluck('total', 'payment_id')
            ->map(fn ($total) => Money::toCents((string) $total));
    }

    private function paymentCents(int $paymentId): int
    {
        $amount = Payment::query()->whereKey($paymentId)->value('amount');

        // A null or negative amount is itself a discrepancy (negative_amount);
        // here it is treated as 0 so any positive refund against it still trips
        // over_refund and the non-negative money parser is never handed a minus.
        if ($amount === null || str_starts_with((string) $amount, '-')) {
            return 0;
        }

        return Money::toCents((string) $amount);
    }

    private function expectedFor(string $code, ?Payment $payment): ?string
    {
        return match ($code) {
            'invoice_total_mismatch', 'final_total_mismatch', 'full_refund_amount_mismatch', 'over_refund' => $payment ? Money::format($payment->amount) : null,
            default => null,
        };
    }

    private function actualFor(string $code, ?Payment $payment): ?string
    {
        if ($payment === null) {
            return null;
        }

        return match ($code) {
            'invoice_total_mismatch' => Money::format(Invoice::where('service_request_id', $payment->service_request_id)->value('total')),
            'final_total_mismatch' => Money::format($payment->request?->final_total),
            'over_refund', 'full_refund_amount_mismatch' => Money::format(
                Money::toDecimalString($this->refundSumsByPayment()->get($payment->id, 0))
            ),
            'missing_invoice' => __('payments.recon_none'),
            default => null,
        };
    }
}
