<?php

namespace Tests\Feature\Unit;

use App\Analytics\WorkerAnalytics;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Phase 29 — worker dashboard analytics. Every query must be scoped to the
 * given worker; a chart can only ever aggregate that worker's own jobs.
 */
class WorkerAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private WorkerAnalytics $analytics;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-06-15 12:00:00');
        $this->analytics = new WorkerAnalytics;
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_jobs_by_status_is_zero_filled_in_workflow_order(): void
    {
        $worker = User::factory()->worker()->create();
        ServiceRequest::factory()->count(2)->status('completed')->create(['worker_id' => $worker->id]);
        ServiceRequest::factory()->status('in_progress')->create(['worker_id' => $worker->id]);

        $series = $this->analytics->jobsByStatus($worker);

        $this->assertCount(8, $series);
        $byLabel = collect($series)->pluck('value', 'label');
        $this->assertSame(2, $byLabel[__('status.request.completed')]);
        $this->assertSame(1, $byLabel[__('status.request.in_progress')]);
        $this->assertSame(0, $byLabel[__('status.request.pending')]);
        $this->assertSame(__('status.request.pending'), $series[0]['label']);
        $this->assertSame(__('status.request.rejected'), $series[7]['label']);
    }

    public function test_jobs_by_status_excludes_other_workers_jobs(): void
    {
        $mine = User::factory()->worker()->create();
        $other = User::factory()->worker()->create();
        ServiceRequest::factory()->status('completed')->create(['worker_id' => $mine->id]);
        ServiceRequest::factory()->count(5)->status('completed')->create(['worker_id' => $other->id]);

        $byLabel = collect($this->analytics->jobsByStatus($mine))->pluck('value', 'label');

        $this->assertSame(1, $byLabel[__('status.request.completed')]);
    }

    public function test_completed_jobs_per_day_is_a_contiguous_30_day_series(): void
    {
        $series = $this->analytics->completedJobsPerDay(User::factory()->worker()->create());

        $this->assertCount(30, $series);
        $this->assertSame('2026-05-17', $series[0]['date']->toDateString());
        $this->assertSame('2026-06-15', $series[29]['date']->toDateString());
        $this->assertSame(0, collect($series)->sum('value'));
    }

    public function test_completed_jobs_per_day_buckets_on_completed_at(): void
    {
        $worker = User::factory()->worker()->create();

        $r1 = ServiceRequest::factory()->status('completed')->create(['worker_id' => $worker->id]);
        $r1->forceFill(['created_at' => Carbon::parse('2026-01-01'), 'completed_at' => Carbon::parse('2026-06-10 09:00:00')])->save();

        $r2 = ServiceRequest::factory()->status('completed')->create(['worker_id' => $worker->id]);
        $r2->forceFill(['completed_at' => Carbon::parse('2026-06-10 20:00:00')])->save();

        // completed but no completed_at timestamp -> not counted
        $r3 = ServiceRequest::factory()->status('completed')->create(['worker_id' => $worker->id]);
        $r3->forceFill(['completed_at' => null])->save();

        $byDate = collect($this->analytics->completedJobsPerDay($worker))->keyBy(fn ($p) => $p['date']->toDateString());

        $this->assertSame(2, $byDate['2026-06-10']['value']);
        $this->assertSame(0, $byDate['2026-06-11']['value']);
        $this->assertSame(2, collect($this->analytics->completedJobsPerDay($worker))->sum('value'));
    }

    public function test_completed_jobs_per_day_ignores_another_workers_completions(): void
    {
        $mine = User::factory()->worker()->create();
        $other = User::factory()->worker()->create();

        $r = ServiceRequest::factory()->status('completed')->create(['worker_id' => $other->id]);
        $r->forceFill(['completed_at' => Carbon::parse('2026-06-10 09:00:00')])->save();

        $this->assertSame(0, collect($this->analytics->completedJobsPerDay($mine))->sum('value'));
    }
}
