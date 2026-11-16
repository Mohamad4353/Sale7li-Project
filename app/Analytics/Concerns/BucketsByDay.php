<?php

namespace App\Analytics\Concerns;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Shared helpers for the dashboard analytics classes: a rolling-window start
 * date, PHP-side day bucketing, and a zero-filled categorical breakdown.
 *
 * All bucketing is done in PHP — no database-specific date SQL — so the same
 * code runs on SQLite in tests and MySQL in production.
 */
trait BucketsByDay
{
    protected function windowStart(int $days): Carbon
    {
        return Carbon::now()->subDays(max(1, $days) - 1)->startOfDay();
    }

    /**
     * A contiguous list of daily buckets [from .. today], each filled with the
     * aggregate of the $rows that fall on that day (oldest first).
     *
     * @param  Collection<int, object>  $rows
     * @param  callable(object): ?string  $dateKey  row -> 'Y-m-d' (null to skip the row)
     * @param  callable(object): (int|float)  $amount  row -> value to add
     * @return list<array{date:Carbon, label:string, value:int|float}>
     */
    protected function dailySeries(int $days, Carbon $from, Collection $rows, callable $dateKey, callable $amount): array
    {
        $days = max(1, $days);

        $buckets = [];
        for ($offset = 0; $offset < $days; $offset++) {
            $buckets[$from->copy()->addDays($offset)->toDateString()] = 0;
        }

        foreach ($rows as $row) {
            $key = $dateKey($row);
            if ($key !== null && array_key_exists($key, $buckets)) {
                $buckets[$key] += $amount($row);
            }
        }

        $series = [];
        foreach ($buckets as $date => $value) {
            $carbon = Carbon::parse($date);
            $series[] = ['date' => $carbon, 'label' => $carbon->translatedFormat('M j'), 'value' => $value];
        }

        return $series;
    }

    /**
     * A fixed-order, zero-filled series with translated labels for a
     * categorical bar chart.
     *
     * @param  list<string>  $keys  the full, ordered set of expected keys
     * @param  Collection<string, int|string>|array<string, int|string>  $counts  key => count
     * @param  string  $labelPrefix  e.g. "status.request." — the key is appended
     * @return list<array{label:string, value:int}>
     */
    protected function labelledBreakdown(array $keys, Collection|array $counts, string $labelPrefix): array
    {
        $counts = collect($counts);

        return array_map(fn (string $key): array => [
            'label' => __($labelPrefix.$key),
            'value' => (int) $counts->get($key, 0),
        ], $keys);
    }
}
