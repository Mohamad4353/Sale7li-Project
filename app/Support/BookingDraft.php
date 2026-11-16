<?php

namespace App\Support;

use Illuminate\Support\Arr;

/**
 * A per-session scratch copy of the customer's in-progress booking form, so a
 * detour to the address screen (or a stray click) does not wipe what they typed.
 *
 * Strictly non-authoritative — every value here is re-validated by
 * StoreBookingRequest on the real submit. Only these six scalar fields are ever
 * stored: never an uploaded file, never anything financial (status, worker_id,
 * quoted_total, final_total, payment_status, reference, …).
 */
final class BookingDraft
{
    private const KEY = 'booking.draft';

    /** @var list<string> */
    public const FIELDS = [
        'service_category_id',
        'problem_description',
        'is_emergency',
        'address_id',
        'preferred_date',
        'preferred_time',
    ];

    /**
     * @param  array<string, mixed>  $input
     */
    public static function put(array $input): void
    {
        $clean = array_filter(
            Arr::only($input, self::FIELDS),
            static fn ($value) => is_scalar($value),
        );

        session()->put(self::KEY, $clean);
    }

    /**
     * @return array<string, scalar>
     */
    public static function get(): array
    {
        return Arr::only((array) session()->get(self::KEY, []), self::FIELDS);
    }

    public static function forget(): void
    {
        session()->forget(self::KEY);
    }
}
