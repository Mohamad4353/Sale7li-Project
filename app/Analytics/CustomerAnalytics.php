<?php

namespace App\Analytics;

use App\Analytics\Concerns\BucketsByDay;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Read-only analytics for the signed-in customer's own dashboard.
 *
 * Every query is scoped to the given customer's id. Output is counts only — no
 * worker data, no PII.
 */
final class CustomerAnalytics
{
    use BucketsByDay;

    public const WINDOW_DAYS = 30;

    /**
     * The customer's requests by status (zero-filled, workflow order).
     *
     * @return list<array{label:string, value:int}>
     */
    public function requestsByStatus(User $customer): array
    {
        $counts = ServiceRequest::query()
            ->where('customer_id', $customer->id)
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return $this->labelledBreakdown(AdminAnalytics::REQUEST_STATUSES, $counts, 'status.request.');
    }

    /**
     * The customer's requests created per calendar day over the last $days days.
     *
     * @return list<array{date:Carbon, label:string, value:int}>
     */
    public function requestsPerDay(User $customer, int $days = self::WINDOW_DAYS): array
    {
        $from = $this->windowStart($days);

        $rows = ServiceRequest::query()
            ->where('customer_id', $customer->id)
            ->where('created_at', '>=', $from)
            ->get(['created_at']);

        return $this->dailySeries($days, $from, $rows, fn ($row) => $row->created_at?->toDateString(), fn () => 1);
    }
}
