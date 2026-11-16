<?php

namespace Tests\Feature\Worker;

use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\WorkerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 29 — the worker dashboard renders its two charts, scoped to the
 * signed-in professional only, inside the existing worker-only route.
 */
class WorkerDashboardChartsTest extends TestCase
{
    use RefreshDatabase;

    private function worker(): User
    {
        $worker = User::factory()->worker()->create();
        WorkerProfile::factory()->for($worker, 'user')->create();

        return $worker;
    }

    public function test_dashboard_shows_both_worker_charts(): void
    {
        $worker = $this->worker();
        ServiceRequest::factory()->count(2)->status('completed')->create(['worker_id' => $worker->id]);

        $response = $this->actingAs($worker)->get(route('worker.dashboard'))->assertOk();

        $response->assertSeeText(__('dashboard.chart_my_jobs_status'));
        $response->assertSeeText(__('dashboard.chart_my_completed_30d'));
        $response->assertSee('data-chart="worker-jobs-by-status"', false);
        $response->assertSee('data-chart="worker-completed-30d"', false);
    }

    public function test_charts_only_reflect_the_signed_in_workers_data(): void
    {
        $mine = $this->worker();
        $other = User::factory()->worker()->create();
        WorkerProfile::factory()->for($other, 'user')->create();

        // The OTHER worker has 6 recent completions; I have none of my own.
        ServiceRequest::factory()->count(6)->status('completed')->create(['worker_id' => $other->id])
            ->each(fn ($r) => $r->forceFill(['completed_at' => now()->subDays(3)])->save());

        $response = $this->actingAs($mine)->get(route('worker.dashboard'))->assertOk();

        // My "completed jobs · last 30 days" line chart has no data of its own —
        // if scoping were broken it would show the other worker's 6 completions.
        $response->assertSee('data-chart="worker-completed-30d"', false);
        $response->assertSeeText(__('dashboard.chart_no_data'));
    }

    public function test_charts_render_with_an_empty_workload(): void
    {
        $this->actingAs($this->worker())->get(route('worker.dashboard'))
            ->assertOk()
            ->assertSeeText(__('dashboard.chart_no_data'));
    }

    public function test_worker_charts_render_in_arabic_without_raw_keys(): void
    {
        $worker = $this->worker();
        ServiceRequest::factory()->status('completed')->create(['worker_id' => $worker->id]);

        $response = $this->actingAs($worker)->withSession(['locale' => 'ar'])
            ->get(route('worker.dashboard'))->assertOk();

        $response->assertSeeText(__('dashboard.chart_my_jobs_status', [], 'ar'));
        $response->assertDontSee('dashboard.chart');
        $response->assertDontSee('status.request.');
    }

    public function test_worker_dashboard_stays_worker_only(): void
    {
        $this->get(route('worker.dashboard'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->customer()->create())->get(route('worker.dashboard'))->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get(route('worker.dashboard'))->assertForbidden();
    }
}
