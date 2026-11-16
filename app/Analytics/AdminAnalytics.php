<?php

namespace App\Analytics;

use App\Analytics\Concerns\BucketsByDay;
use App\Models\Payment;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\WorkerProfile;
use Illuminate\Support\Carbon;

/**
 * Read-only operational analytics for the admin dashboard.
 *
 * Every figure is a live aggregate over existing tables — there is no analytics
 * table, no cache, and no financial computation. "Captured revenue" is exactly
 * the dashboard's pre-existing definition (the sum of the `amount` column over
 * payments whose status is `paid`); nothing about how money is computed or
 * stored is touched here.
 *
 * Time series are bucketed in PHP so the queries stay portable across SQLite
 * (tests) and MySQL (production) with no database-specific date SQL.
 */
final class AdminAnalytics
{
    use BucketsByDay;

    /** The full service-request status enum, in workflow order (single source of truth). */
    public const REQUEST_STATUSES = ['pending', 'accepted', 'scheduled', 'on_the_way', 'in_progress', 'completed', 'cancelled', 'rejected'];

    /** The full worker verification status enum. */
    public const VERIFICATION_STATUSES = ['pending', 'verified', 'rejected', 'suspended'];

    /** Terminal "did not complete" statuses — mirrors `cancelled_requests`. */
    private const ENDED = ['cancelled', 'rejected'];

    /** Default rolling window for the KPIs and the time-series charts. */
    public const WINDOW_DAYS = 30;

    /**
     * Headline KPIs that complement — never replace — the existing $stats.
     *
     * @return array{verified_workers:int, completion_rate:float, cancellation_rate:float, avg_requests_per_day:float}
     */
    public function kpis(int $windowDays = self::WINDOW_DAYS): array
    {
        $completed = ServiceRequest::where('status', 'completed')->count();
        $ended = ServiceRequest::whereIn('status', self::ENDED)->count();
        $decided = $completed + $ended;

        $inWindow = ServiceRequest::where('created_at', '>=', $this->windowStart($windowDays))->count();

        return [
            'verified_workers' => WorkerProfile::where('verification_status', 'verified')->count(),
            'completion_rate' => $decided > 0 ? round($completed / $decided * 100, 1) : 0.0,
            'cancellation_rate' => $decided > 0 ? round($ended / $decided * 100, 1) : 0.0,
            'avg_requests_per_day' => round($inWindow / max(1, $windowDays), 1),
        ];
    }

    /**
     * Requests created per calendar day over the last $days days (oldest first).
     *
     * @return list<array{date:Carbon, label:string, value:int}>
     */
    public function requestsPerDay(int $days = self::WINDOW_DAYS): array
    {
        $from = $this->windowStart($days);

        $rows = ServiceRequest::query()
            ->where('created_at', '>=', $from)
            ->get(['created_at']);

        return $this->dailySeries(
            $days,
            $from,
            $rows,
            fn ($row) => $row->created_at?->toDateString(),
            fn () => 1,
        );
    }

    /**
     * Captured (paid) revenue per calendar day over the last $days days.
     *
     * Source: payments with status = 'paid', summing the existing `amount`
     * column by `paid_at` — the identical filter behind the dashboard's
     * `revenue` KPI, only bucketed by day.
     *
     * @return list<array{date:Carbon, label:string, value:float}>
     */
    public function revenuePerDay(int $days = self::WINDOW_DAYS): array
    {
        $from = $this->windowStart($days);

        $rows = Payment::query()
            ->where('status', 'paid')
            ->whereNotNull('paid_at')
            ->where('paid_at', '>=', $from)
            ->get(['paid_at', 'amount']);

        return $this->dailySeries(
            $days,
            $from,
            $rows,
            fn ($row) => $row->paid_at?->toDateString(),
            fn ($row) => (float) $row->amount,
        );
    }

    /**
     * Total requests per service category, busiest first.
     *
     * @return list<array{label:string, value:int}>
     */
    public function requestsByCategory(): array
    {
        return ServiceCategory::query()
            ->withCount('requests')
            ->orderByDesc('requests_count')
            ->orderBy('name')
            ->get()
            ->map(fn (ServiceCategory $category) => [
                'label' => $category->localized_name,
                'value' => (int) $category->requests_count,
            ])
            ->all();
    }

    /**
     * Every request status with its live count (zero-filled), in workflow order.
     *
     * @return list<array{label:string, value:int}>
     */
    public function requestsByStatus(): array
    {
        $counts = ServiceRequest::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return $this->labelledBreakdown(self::REQUEST_STATUSES, $counts, 'status.request.');
    }

    /**
     * New customer accounts created per calendar day over the last $days days.
     *
     * @return list<array{date:Carbon, label:string, value:int}>
     */
    public function newCustomersPerDay(int $days = self::WINDOW_DAYS): array
    {
        $from = $this->windowStart($days);

        $rows = User::query()
            ->where('role', 'customer')
            ->where('created_at', '>=', $from)
            ->get(['created_at']);

        return $this->dailySeries($days, $from, $rows, fn ($row) => $row->created_at?->toDateString(), fn () => 1);
    }

    /**
     * Every worker verification status with its live count (zero-filled).
     *
     * @return list<array{label:string, value:int}>
     */
    public function workerVerificationBreakdown(): array
    {
        $counts = WorkerProfile::query()
            ->selectRaw('verification_status, count(*) as aggregate')
            ->groupBy('verification_status')
            ->pluck('aggregate', 'verification_status');

        return $this->labelledBreakdown(self::VERIFICATION_STATUSES, $counts, 'status.verification.');
    }
}
