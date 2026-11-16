<?php

namespace App\Payments;

use App\Models\PlatformSetting;
use InvalidArgumentException;

/**
 * Server-side marketplace commission calculation.
 *
 * SOURCE OF TRUTH
 * ---------------
 * The active rate is read only from the `platform_settings` row (key
 * `commission_rate`). It is never taken from request, worker, customer or
 * payment input. `DEFAULT_RATE` is a last-resort fallback for a missing row
 * (a misconfiguration), not a second configurable source.
 *
 * MONEY / ROUNDING POLICY
 * -----------------------
 * All arithmetic is integer. The rate is a 4-decimal fraction ("0.1000" = 10%),
 * converted to integer basis points (× 10000) so the multiply is exact:
 *
 *     commission_cents = floor((gross_cents * rate_bps + 5000) / 10000)   // ROUND HALF UP
 *     worker_net_cents = gross_cents - commission_cents
 *
 * Invariants (guaranteed by the rate cap of 0.9000 and the guards below):
 *     0 <= commission_cents <= gross_cents
 *     worker_net_cents = gross_cents - commission_cents >= 0
 */
final class CommissionService
{
    public const DEFAULT_RATE = '0.1000';

    private const MAX_BASIS_POINTS = 9000; // 0.9000 = 90%

    public function activeRate(): string
    {
        return $this->normaliseRate(
            PlatformSetting::getValue(PlatformSetting::COMMISSION_RATE, self::DEFAULT_RATE)
        );
    }

    /**
     * Canonicalise a validated rate string to 4dp and re-assert the 0 – 0.9000
     * bound. Public entry point for the admin settings flow — no new logic,
     * just exposes the existing validation/canonicalisation.
     */
    public function normalizeRate(string $rate): string
    {
        return $this->normaliseRate($rate);
    }

    public function calculate(int|string|float $grossAmount, ?string $rate = null): CommissionResult
    {
        $grossCents = Money::toCents($grossAmount);
        $rate = $rate === null ? $this->activeRate() : $this->normaliseRate($rate);
        $rateBps = $this->rateToBasisPoints($rate);

        $commissionCents = intdiv($grossCents * $rateBps + 5000, 10000);
        $netCents = $grossCents - $commissionCents;

        if ($commissionCents < 0 || $commissionCents > $grossCents || $netCents < 0) {
            throw new InvalidArgumentException('Commission calculation produced an invalid result.');
        }

        return new CommissionResult($grossCents, $rate, $commissionCents, $netCents);
    }

    /** Canonicalise any accepted rate string to 4dp and enforce the 0 – 0.9000 bound. */
    private function normaliseRate(?string $rate): string
    {
        if ($rate === null || ! is_numeric($rate)) {
            throw new InvalidArgumentException('Commission rate must be numeric: ['.($rate ?? 'null').'].');
        }

        $bps = $this->rateToBasisPoints($rate);

        if ($bps < 0 || $bps > self::MAX_BASIS_POINTS) {
            throw new InvalidArgumentException("Commission rate must be between 0 and 0.9000: [{$rate}].");
        }

        return intdiv($bps, 10000).'.'.str_pad((string) ($bps % 10000), 4, '0', STR_PAD_LEFT);
    }

    /** Parse a numeric rate string to integer basis points (× 10000) with no float math. */
    private function rateToBasisPoints(string $rate): int
    {
        $string = trim($rate);
        $negative = str_starts_with($string, '-');
        $string = ltrim($string, '+-');

        [$whole, $fraction] = array_pad(explode('.', $string, 2), 2, '');
        $bps = (int) $whole * 10000 + (int) substr($fraction.'0000', 0, 4);

        return $negative ? -$bps : $bps;
    }
}
