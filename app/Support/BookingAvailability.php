<?php

namespace App\Support;

use App\Models\User;
use Carbon\CarbonInterface;

/**
 * Advisory, read-only "is anyone likely free" calculation for the booking form.
 *
 * It counts verified, active professionals who offer the chosen category and
 * whose recurring weekly availability covers the chosen weekday (and time
 * window, when a time is given). It returns only a count and a boolean — never a
 * worker identity or any other data.
 *
 * It is NEVER a booking gate: publishing availability is optional for
 * professionals by design, so a zero here must not block a request.
 */
final class BookingAvailability
{
    /**
     * @return array{count: int, covered: bool}
     */
    public static function coverage(int $categoryId, CarbonInterface $date, ?string $time = null): array
    {
        // Schema weekday: 0 = Monday … 6 = Sunday (Carbon ISO 1..7 → 0..6).
        $weekday = $date->dayOfWeekIso - 1;

        $count = User::query()
            ->where('role', 'worker')
            ->where('status', 'active')
            ->whereHas('workerProfile', fn ($q) => $q->where('verification_status', 'verified'))
            ->whereHas('workerServices', fn ($q) => $q
                ->where('service_category_id', $categoryId)
                ->where('is_active', true))
            ->whereHas('workerProfile.availability', function ($q) use ($weekday, $time) {
                $q->where('day_of_week', $weekday)->where('is_available', true);

                if ($time !== null) {
                    $q->where(fn ($w) => $w
                        ->whereNull('start_time')
                        ->orWhereNull('end_time')
                        ->orWhere(fn ($b) => $b
                            ->where('start_time', '<=', $time)
                            ->where('end_time', '>=', $time)));
                }
            })
            ->count();

        return ['count' => $count, 'covered' => $count > 0];
    }
}
