<?php

namespace App\Analytics;

use App\Analytics\Concerns\BucketsByDay;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Read-only analytics for the signed-in professional's own dashboard.
 *
 * Every query is scoped to the given worker's id, so a chart can only ever
 * aggregate that worker's own jobs. Output is counts only — no customer data,
 * no other worker's data, no PII.
 */
final class WorkerAnalytics
{
    use BucketsByDay;

    public const WINDOW_DAYS = 30;

    /**
     * The worker's assigned jobs by status (zero-filled, workflow order).
     *
     * @return list<array{label:string, value:int}>
     */
    public function jobsByStatus(User $worker): array
    {
        $counts = ServiceRequest::query()
            ->where('worker_id', $worker->id)
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return $this->labelledBreakdown(AdminAnalytics::REQUEST_STATUSES, $counts, 'status.request.');
    }

    /**
     * The worker's completed jobs per calendar day over the last $days days,
     * bucketed on completed_at (the timestamp the completion transition sets).
     *
     * @return list<array{date:Carbon, label:string, value:int}>
     */
    public function completedJobsPerDay(User $worker, int $days = self::WINDOW_DAYS): array
    {
        $from = $this->windowStart($days);

        $rows = ServiceRequest::query()
            ->where('worker_id', $worker->id)
            ->where('status', 'completed')
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', $from)
            ->get(['completed_at']);

        return $this->dailySeries($days, $from, $rows, fn ($row) => $row->completed_at?->toDateString(), fn () => 1);
    }
}
