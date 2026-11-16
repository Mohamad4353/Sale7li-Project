<?php

namespace App\Payments;

use App\Models\ActivityLog;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Internal, application-level refunds.
 *
 * A refund is a new ledger fact: it NEVER mutates the Payment it reverses. The
 * payment keeps its frozen amount / commission_rate / commission_amount /
 * worker_net and its `paid` status. Refund figures are derived from Refund rows
 * only, using integer-cent arithmetic (no float, no live commission rate).
 *
 * There is no external gateway — a `completed` refund is a confirmed
 * application-level state, not a PSP transaction, and no gateway id is stored.
 */
class RefundService
{
    /**
     * Fully refund an eligible paid payment. Atomic: the refund row AND its
     * audit entry are written in one transaction — a failure of either leaves
     * no partial ledger state. The row lock + the UNIQUE(payment_id, type)
     * constraint together guarantee at most one full refund even under
     * concurrent admin requests.
     *
     * @throws RefundException when the payment is not eligible
     */
    public function fullyRefund(Payment $payment, ?User $actor = null, ?string $reason = null): Refund
    {
        return DB::transaction(function () use ($payment, $actor, $reason) {
            /** @var Payment $locked */
            $locked = Payment::query()->whereKey($payment->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'paid') {
                throw RefundException::notPaid();
            }

            if ($this->fullRefundExists($locked->id)) {
                throw RefundException::alreadyRefunded();
            }

            $alreadyRefundedCents = $this->completedRefundCents($locked->id);
            $paymentCents = Money::toCents($locked->amount);
            $refundCents = $paymentCents - $alreadyRefundedCents;

            if ($refundCents <= 0) {
                throw RefundException::nothingToRefund();
            }

            // Defensive: full refunds make this exact, never greater. Guards
            // corrupt/manual ledger data and a future partial-refund phase.
            if ($alreadyRefundedCents + $refundCents > $paymentCents) {
                throw RefundException::exceedsPayment();
            }

            $refund = Refund::create([
                'payment_id' => $locked->id,
                'amount' => Money::toDecimalString($refundCents),
                'type' => Refund::TYPE_FULL,
                'status' => Refund::STATUS_COMPLETED,
                'reason' => $reason,
                'processed_by' => $actor?->id,
                'processed_at' => now(),
            ]);

            ActivityLog::create([
                'user_id' => $actor?->id,
                'action' => 'refund.completed',
                'subject_type' => Payment::class,
                'subject_id' => $locked->id,
                'metadata' => [
                    'payment_id' => $locked->id,
                    'service_request_id' => $locked->service_request_id,
                    'refund_id' => $refund->id,
                    'amount' => $refund->amount,
                ],
                'ip_address' => request()?->ip(),
            ]);

            return $refund;
        });
    }

    /** Total already refunded on a payment, in cents (completed refunds only). */
    public function refundedCents(Payment $payment): int
    {
        if ($payment->relationLoaded('refunds')) {
            return $payment->refunds
                ->where('status', Refund::STATUS_COMPLETED)
                ->reject(fn (Refund $r) => str_starts_with((string) $r->amount, '-'))
                ->reduce(fn (int $carry, Refund $r) => $carry + Money::toCents($r->amount), 0);
        }

        return $this->completedRefundCents($payment->id);
    }

    public function isFullyRefunded(Payment $payment): bool
    {
        return $payment->amount !== null
            && $this->refundedCents($payment) >= Money::toCents($payment->amount)
            && $this->refundedCents($payment) > 0;
    }

    private function completedRefundCents(int $paymentId): int
    {
        $sum = Refund::query()
            ->where('payment_id', $paymentId)
            ->where('status', Refund::STATUS_COMPLETED)
            ->where('amount', '>=', 0)
            ->sum('amount');

        return Money::toCents((string) $sum);
    }

    private function fullRefundExists(int $paymentId): bool
    {
        return Refund::query()
            ->where('payment_id', $paymentId)
            ->where('type', Refund::TYPE_FULL)
            ->where('status', Refund::STATUS_COMPLETED)
            ->exists();
    }
}
