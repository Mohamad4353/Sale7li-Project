<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Refund;
use App\Payments\Money;

/**
 * Read-only earnings for the authenticated professional.
 *
 * Every figure is a persisted snapshot: gross / commission / net come from the
 * Payment columns frozen at capture (Step 2); "refunded" and "retained" come
 * from Refund rows (Step 4). Nothing here reads the live platform commission
 * rate or recomputes a historical value. Ownership is `payments.worker_id`.
 */
class EarningsController extends Controller
{
    public function index()
    {
        $workerId = auth()->id();

        $base = Payment::query()->where('worker_id', $workerId)->where('status', 'paid');

        $totals = (clone $base)
            ->selectRaw('COUNT(*) as jobs')
            ->selectRaw('COALESCE(SUM(amount), 0) as gross')
            ->selectRaw('COALESCE(SUM(commission_amount), 0) as commission')
            ->selectRaw('COALESCE(SUM(worker_net), 0) as net')
            ->first();

        // Refunds attributable to this professional's captured payments.
        $refundedCents = Money::toCents((string) Refund::query()
            ->where('refunds.status', Refund::STATUS_COMPLETED)
            ->join('payments', 'payments.id', '=', 'refunds.payment_id')
            ->where('payments.worker_id', $workerId)
            ->where('payments.status', 'paid')
            ->sum('refunds.amount'));

        // Net that has been reversed: the worker_net of fully-refunded payments.
        $reversedNetCents = Money::toCents((string) (clone $base)
            ->whereHas('refunds', fn ($r) => $r->where('type', Refund::TYPE_FULL)->where('status', Refund::STATUS_COMPLETED))
            ->sum('worker_net'));

        $retained = Money::toDecimalString(max(0, Money::toCents((string) $totals->net) - $reversedNetCents));
        $refunded = Money::toDecimalString($refundedCents);

        $payments = (clone $base)
            ->with([
                'request:id,reference,service_category_id',
                'request.category:id,name,name_ar',
                'customer:id,name',
                'refunds',
            ])
            ->latest('paid_at')
            ->paginate(15);

        return view('worker.earnings', compact('totals', 'payments', 'refunded', 'retained'));
    }
}
