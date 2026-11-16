<?php

namespace App\Payments\Reconciliation;

/**
 * The outcome of a financial reconciliation pass: SQL-aggregated totals plus a
 * list of detected discrepancies. Read-only; produced by ReconciliationService.
 *
 * All money values are canonical "x.xx" strings computed with integer-cent
 * arithmetic — never PHP floats, never the live commission rate.
 */
final readonly class ReconciliationReport
{
    /**
     * @param  array<int, Discrepancy>  $discrepancies
     */
    public function __construct(
        public int $checked,
        public int $reconciled,
        public int $refundedPayments,
        public string $grossCaptured,
        public string $totalRefunded,
        public string $retained,
        public array $discrepancies,
    ) {}

    public function discrepancyCount(): int
    {
        return count($this->discrepancies);
    }

    public function isClean(): bool
    {
        return $this->discrepancies === [];
    }
}
