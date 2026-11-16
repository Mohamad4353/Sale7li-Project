<?php

namespace App\Payments;

use InvalidArgumentException;

/**
 * Integer-cent money handling for the financial core.
 *
 * MONEY POLICY
 * ------------
 * - All financial arithmetic is done on integers (cents). No float multiply,
 *   divide or subtract is used anywhere in commission/net calculation.
 * - Decimal strings coming out of the database (decimal(10,2) columns are cast
 *   as strings by Eloquent) are parsed digit-by-digit, never via `(float)`.
 * - A value with a stray third decimal is rounded HALF UP to the nearest cent.
 * - Money in this domain is non-negative; a negative input is a programming
 *   error and throws.
 * - Results are rendered back to a canonical "x.xx" string for persistence into
 *   the existing decimal(10,2) columns.
 */
final class Money
{
    public static function toCents(int|string|float $amount): int
    {
        if (is_int($amount)) {
            return self::guard($amount * 100);
        }

        // A float caller is tolerated defensively but normalised through a
        // 2-dp string first so the digit parser below stays authoritative.
        $string = is_float($amount) ? number_format($amount, 2, '.', '') : trim($amount);

        if (! preg_match('/^\d+(\.\d+)?$/', $string)) {
            throw new InvalidArgumentException("Not a non-negative money value: [{$string}]");
        }

        [$whole, $fraction] = array_pad(explode('.', $string, 2), 2, '');

        $cents = (int) $whole * 100 + (int) substr($fraction.'00', 0, 2);

        if (strlen($fraction) > 2 && (int) $fraction[2] >= 5) {
            $cents++; // round half up on an over-precise input
        }

        return self::guard($cents);
    }

    public static function toDecimalString(int $cents): string
    {
        $cents = self::guard($cents);

        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    /**
     * Display formatting only. Groups the integer part with thousands
     * separators and keeps two decimals — done on strings, never via float.
     * Returns null for a null input so callers can render "—" instead of a
     * fabricated $0.00 for a genuinely absent value.
     */
    public static function format(int|string|float|null $amount, string $symbol = '$'): ?string
    {
        if ($amount === null) {
            return null;
        }

        // Display tolerates a leading minus (invoice line items may be negative,
        // e.g. a discount). The strict integer parser below still only sees a
        // non-negative magnitude.
        $string = is_string($amount) ? trim($amount) : (string) $amount;
        $negative = str_starts_with($string, '-');

        $cents = self::toCents($negative ? ltrim($string, '-') : $string);
        $whole = (string) intdiv($cents, 100);
        $fraction = str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
        $grouped = strrev(implode(',', str_split(strrev($whole), 3)));

        return ($negative && $cents !== 0 ? '-' : '').$symbol.$grouped.'.'.$fraction;
    }

    /**
     * A stored commission-rate fraction as a percent label — "0.1000" → "10%",
     * "0.3333" → "33.33%". String math only; display use only; null-safe.
     */
    public static function ratePercent(int|string|null $rate): ?string
    {
        if ($rate === null) {
            return null;
        }

        [$whole, $fraction] = array_pad(explode('.', trim((string) $rate), 2), 2, '');
        $basisPoints = (int) $whole * 10000 + (int) substr($fraction.'0000', 0, 4);

        $percentWhole = intdiv($basisPoints, 100);
        $percentFraction = $basisPoints % 100;

        return $percentFraction === 0
            ? $percentWhole.'%'
            : $percentWhole.'.'.rtrim(str_pad((string) $percentFraction, 2, '0', STR_PAD_LEFT), '0').'%';
    }

    private static function guard(int $cents): int
    {
        if ($cents < 0) {
            throw new InvalidArgumentException('Money amounts cannot be negative.');
        }

        return $cents;
    }
}
