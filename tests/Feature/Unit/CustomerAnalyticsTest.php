<?php

namespace Tests\Feature\Unit;

use App\Analytics\CustomerAnalytics;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Phase 29 — customer dashboard analytics. Every query must be scoped to the
 * given customer.
 */
class CustomerAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private CustomerAnalytics $analytics;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-06-15 12:00:00');
        $this->analytics = new CustomerAnalytics;
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_requests_by_status_is_zero_filled_and_scoped(): void
    {
        $mine = User::factory()->customer()->create();
        $other = User::factory()->customer()->create();

        ServiceRequest::factory()->count(3)->status('pending')->create(['customer_id' => $mine->id]);
        ServiceRequest::factory()->status('completed')->create(['customer_id' => $mine->id]);
        ServiceRequest::factory()->count(9)->status('pending')->create(['customer_id' => $other->id]);

        $series = $this->analytics->requestsByStatus($mine);

        $this->assertCount(8, $series);
        $byLabel = collect($series)->pluck('value', 'label');
        $this->assertSame(3, $byLabel[__('status.request.pending')]);
        $this->assertSame(1, $byLabel[__('status.request.completed')]);
        $this->assertSame(0, $byLabel[__('status.request.rejected')]);
    }

    public function test_requests_per_day_is_a_contiguous_30_day_series_oldest_first(): void
    {
        $series = $this->analytics->requestsPerDay(User::factory()->customer()->create());

        $this->assertCount(30, $series);
        $this->assertSame('2026-05-17', $series[0]['date']->toDateString());
        $this->assertSame('2026-06-15', $series[29]['date']->toDateString());
        $this->assertSame(0, collect($series)->sum('value'));
    }

    public function test_requests_per_day_buckets_by_creation_day_and_window(): void
    {
        $customer = User::factory()->customer()->create();

        $a = ServiceRequest::factory()->create(['customer_id' => $customer->id]);
        $a->forceFill(['created_at' => Carbon::parse('2026-06-12 08:00:00')])->save();
        $b = ServiceRequest::factory()->create(['customer_id' => $customer->id]);
        $b->forceFill(['created_at' => Carbon::parse('2026-06-12 22:00:00')])->save();
        $old = ServiceRequest::factory()->create(['customer_id' => $customer->id]);
        $old->forceFill(['created_at' => Carbon::parse('2026-05-16 23:59:59')])->save(); // before window

        $series = $this->analytics->requestsPerDay($customer);
        $byDate = collect($series)->keyBy(fn ($p) => $p['date']->toDateString());

        $this->assertSame(2, $byDate['2026-06-12']['value']);
        $this->assertSame(2, collect($series)->sum('value'));
    }

    public function test_requests_per_day_excludes_another_customers_requests(): void
    {
        $mine = User::factory()->customer()->create();
        $other = User::factory()->customer()->create();
        ServiceRequest::factory()->count(4)->create(['customer_id' => $other->id]);

        $this->assertSame(0, collect($this->analytics->requestsPerDay($mine))->sum('value'));
    }
}
