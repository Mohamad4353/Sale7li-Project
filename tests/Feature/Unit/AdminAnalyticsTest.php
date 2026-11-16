<?php

namespace Tests\Feature\Unit;

use App\Analytics\AdminAnalytics;
use App\Models\Payment;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\WorkerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Phase 29 — the admin analytics aggregation layer in isolation. Every figure is
 * a live aggregate over existing tables; time series are bucketed in PHP.
 */
class AdminAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private AdminAnalytics $analytics;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-06-15 12:00:00');
        $this->analytics = new AdminAnalytics;
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function requestOn(string $datetime, array $attrs = []): ServiceRequest
    {
        $request = ServiceRequest::factory()->create($attrs);
        $request->forceFill(['created_at' => Carbon::parse($datetime)])->save();

        return $request->refresh();
    }

    /* -------------------------------- kpis -------------------------------- */

    public function test_kpis_completion_and_cancellation_rates(): void
    {
        ServiceRequest::factory()->count(4)->status('completed')->create();
        ServiceRequest::factory()->status('cancelled')->create();
        ServiceRequest::factory()->status('rejected')->create();

        $kpis = $this->analytics->kpis();

        $this->assertSame(66.7, $kpis['completion_rate']);   // 4 / 6
        $this->assertSame(33.3, $kpis['cancellation_rate']); // 2 / 6
    }

    public function test_kpis_rates_are_zero_when_nothing_is_decided(): void
    {
        ServiceRequest::factory()->count(3)->status('pending')->create();

        $kpis = $this->analytics->kpis();

        $this->assertSame(0.0, $kpis['completion_rate']);
        $this->assertSame(0.0, $kpis['cancellation_rate']);
    }

    public function test_kpis_counts_only_verified_worker_profiles(): void
    {
        WorkerProfile::factory()->count(2)->verified()->create();
        WorkerProfile::factory()->create(['verification_status' => 'pending']);
        WorkerProfile::factory()->create(['verification_status' => 'rejected']);

        $this->assertSame(2, $this->analytics->kpis()['verified_workers']);
    }

    public function test_kpis_avg_requests_per_day_over_the_window(): void
    {
        // 15 requests inside the 30-day window, 1 clearly outside it.
        ServiceRequest::factory()->count(15)->create();
        $this->requestOn('2026-01-01 09:00:00');

        $this->assertSame(0.5, $this->analytics->kpis()['avg_requests_per_day']); // 15 / 30
    }

    /* -------------------------- requestsPerDay -------------------------- */

    public function test_requests_per_day_is_a_contiguous_30_day_series_oldest_first(): void
    {
        $series = $this->analytics->requestsPerDay();

        $this->assertCount(30, $series);
        $this->assertSame('2026-05-17', $series[0]['date']->toDateString());
        $this->assertSame('2026-06-15', $series[29]['date']->toDateString());
        $this->assertSame(0, $series[0]['value']);
        foreach ($series as $point) {
            $this->assertArrayHasKey('label', $point);
            $this->assertArrayHasKey('value', $point);
        }
    }

    public function test_requests_per_day_buckets_each_request_on_its_creation_day(): void
    {
        $this->requestOn('2026-06-15 08:00:00');
        $this->requestOn('2026-06-15 22:00:00');
        $this->requestOn('2026-06-10 10:00:00');

        $byDate = collect($this->analytics->requestsPerDay())->keyBy(fn ($p) => $p['date']->toDateString());

        $this->assertSame(2, $byDate['2026-06-15']['value']);
        $this->assertSame(1, $byDate['2026-06-10']['value']);
        $this->assertSame(0, $byDate['2026-06-11']['value']);
    }

    public function test_requests_per_day_excludes_rows_before_the_window_and_includes_the_boundary(): void
    {
        $this->requestOn('2026-05-17 00:00:00'); // exactly the window start
        $this->requestOn('2026-05-16 23:59:59'); // one second before -> excluded

        $series = $this->analytics->requestsPerDay();
        $byDate = collect($series)->keyBy(fn ($p) => $p['date']->toDateString());

        $this->assertSame(1, $byDate['2026-05-17']['value'], 'a row exactly at the window start is included');
        $this->assertSame(1, collect($series)->sum('value'), 'the row one second before the window is excluded');
    }

    public function test_series_length_follows_the_days_argument(): void
    {
        $this->assertCount(7, $this->analytics->requestsPerDay(7));
        $this->assertCount(90, $this->analytics->requestsPerDay(90));
    }

    /* --------------------------- revenuePerDay ------------------------- */

    public function test_revenue_per_day_sums_only_paid_payments_by_paid_at(): void
    {
        Payment::factory()->paid()->create(['amount' => 150, 'paid_at' => Carbon::parse('2026-06-10 10:00:00')]);
        Payment::factory()->paid()->create(['amount' => 50, 'paid_at' => Carbon::parse('2026-06-10 18:00:00')]);
        Payment::factory()->paid()->create(['amount' => 999, 'paid_at' => Carbon::parse('2026-06-12 09:00:00')]);

        $byDate = collect($this->analytics->revenuePerDay())->keyBy(fn ($p) => $p['date']->toDateString());

        $this->assertSame(200.0, $byDate['2026-06-10']['value']);
        $this->assertSame(999.0, $byDate['2026-06-12']['value']);
        $this->assertSame(0, $byDate['2026-06-11']['value']);
    }

    public function test_revenue_per_day_excludes_pending_failed_and_refunded(): void
    {
        Payment::factory()->create(['status' => 'pending', 'amount' => 100, 'paid_at' => null]);
        Payment::factory()->create(['status' => 'failed', 'amount' => 100, 'paid_at' => Carbon::parse('2026-06-10 10:00:00')]);
        Payment::factory()->refunded()->create(['amount' => 100, 'paid_at' => Carbon::parse('2026-06-10 10:00:00')]);
        Payment::factory()->paid()->create(['amount' => 25, 'paid_at' => Carbon::parse('2026-06-10 10:00:00')]);

        $this->assertSame(25.0, (float) collect($this->analytics->revenuePerDay())->sum('value'));
    }

    /* ------------------------- requestsByCategory --------------------- */

    public function test_requests_by_category_counts_and_orders_busiest_first(): void
    {
        $plumbing = ServiceCategory::factory()->create(['name' => 'Plumbing']);
        $electrical = ServiceCategory::factory()->create(['name' => 'Electrical']);
        $painting = ServiceCategory::factory()->create(['name' => 'Painting']);

        ServiceRequest::factory()->count(3)->create(['service_category_id' => $plumbing->id]);
        ServiceRequest::factory()->create(['service_category_id' => $electrical->id]);

        $rows = $this->analytics->requestsByCategory();

        $this->assertSame(['Plumbing', 3], [$rows[0]['label'], $rows[0]['value']]);
        $this->assertSame(['Electrical', 1], [$rows[1]['label'], $rows[1]['value']]);
        $this->assertSame(['Painting', 0], [$rows[2]['label'], $rows[2]['value']]);
    }

    public function test_requests_by_category_includes_categories_with_zero_requests(): void
    {
        ServiceCategory::factory()->count(3)->create();

        $rows = $this->analytics->requestsByCategory();

        $this->assertCount(3, $rows);
        $this->assertSame([0, 0, 0], array_column($rows, 'value'));
    }

    /* -------------------------- requestsByStatus -------------------------- */

    public function test_requests_by_status_returns_all_eight_statuses_zero_filled_in_order(): void
    {
        ServiceRequest::factory()->count(2)->status('pending')->create();
        ServiceRequest::factory()->status('completed')->create();

        $rows = $this->analytics->requestsByStatus();

        $this->assertCount(8, $rows);
        $this->assertSame(__('status.request.pending'), $rows[0]['label']);
        $this->assertSame(__('status.request.rejected'), $rows[7]['label']);

        $byLabel = collect($rows)->pluck('value', 'label');
        $this->assertSame(2, $byLabel[__('status.request.pending')]);
        $this->assertSame(1, $byLabel[__('status.request.completed')]);
        $this->assertSame(0, $byLabel[__('status.request.on_the_way')]);
    }

    /* ------------------------- newCustomersPerDay ------------------------ */

    public function test_new_customers_per_day_counts_only_customers_in_the_window(): void
    {
        // 4 customers inside the window (factory default created_at = now)
        User::factory()->count(4)->customer()->create();
        // a worker and an admin created now must NOT count
        User::factory()->worker()->create();
        User::factory()->admin()->create();
        // a customer created before the window
        $old = User::factory()->customer()->create();
        $old->forceFill(['created_at' => Carbon::parse('2026-01-01')])->save();

        $series = $this->analytics->newCustomersPerDay();

        $this->assertCount(30, $series);
        $this->assertSame(4, collect($series)->sum('value'));
        $this->assertSame(4, $series[29]['value']); // all four on "today"
    }

    /* --------------------- workerVerificationBreakdown ------------------ */

    public function test_worker_verification_breakdown_returns_all_four_states_zero_filled(): void
    {
        WorkerProfile::factory()->count(3)->create(['verification_status' => 'verified']);
        WorkerProfile::factory()->create(['verification_status' => 'pending']);

        $rows = $this->analytics->workerVerificationBreakdown();

        $this->assertCount(4, $rows);
        $byLabel = collect($rows)->pluck('value', 'label');
        $this->assertSame(3, $byLabel[__('status.verification.verified')]);
        $this->assertSame(1, $byLabel[__('status.verification.pending')]);
        $this->assertSame(0, $byLabel[__('status.verification.rejected')]);
        $this->assertSame(0, $byLabel[__('status.verification.suspended')]);
    }
}
