<?php

namespace Tests\Feature\Worker;

use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * P14-08 — server-side status filter pills on the worker job list. Same UI
 * concept as the customer list; grouping maps onto existing workflow states.
 */
class JobStatusFilterTest extends TestCase
{
    use RefreshDatabase;

    private function jobFor(User $worker, string $status): ServiceRequest
    {
        return ServiceRequest::factory()
            ->for(User::factory()->customer()->create(), 'customer')
            ->status($status)
            ->assignedTo($worker)
            ->create(['service_category_id' => ServiceCategory::factory()->create()->id]);
    }

    public function test_default_list_shows_all_assigned_jobs(): void
    {
        $worker = User::factory()->worker()->create();
        $a = $this->jobFor($worker, 'accepted')->reference;
        $b = $this->jobFor($worker, 'completed')->reference;
        $c = $this->jobFor($worker, 'cancelled')->reference;

        $this->actingAs($worker)->get(route('worker.jobs.index'))
            ->assertOk()->assertSee($a)->assertSee($b)->assertSee($c);
    }

    public function test_active_filter_limits_to_in_flight_jobs(): void
    {
        $worker = User::factory()->worker()->create();
        $active = $this->jobFor($worker, 'in_progress')->reference;
        $done = $this->jobFor($worker, 'completed')->reference;

        $this->actingAs($worker)->get(route('worker.jobs.index', ['status' => 'active']))
            ->assertOk()->assertSee($active)->assertDontSee($done);
    }

    public function test_completed_filter_shows_only_completed(): void
    {
        $worker = User::factory()->worker()->create();
        $done = $this->jobFor($worker, 'completed')->reference;
        $active = $this->jobFor($worker, 'scheduled')->reference;

        $this->actingAs($worker)->get(route('worker.jobs.index', ['status' => 'completed']))
            ->assertOk()->assertSee($done)->assertDontSee($active);
    }

    public function test_filter_never_exposes_another_workers_jobs(): void
    {
        $worker = User::factory()->worker()->create();
        $foreign = $this->jobFor(User::factory()->worker()->create(), 'completed');

        $this->actingAs($worker)->get(route('worker.jobs.index', ['status' => 'completed']))
            ->assertOk()->assertDontSee($foreign->reference);
    }

    public function test_pagination_preserves_the_filter(): void
    {
        $worker = User::factory()->worker()->create();
        for ($i = 0; $i < 14; $i++) {
            $this->jobFor($worker, 'completed');
        }

        $this->actingAs($worker)->get(route('worker.jobs.index', ['status' => 'completed']))
            ->assertOk()
            ->assertSee('status=completed', false)
            ->assertSee('page=2', false);
    }
}
