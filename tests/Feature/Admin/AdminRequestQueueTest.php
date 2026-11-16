<?php

namespace Tests\Feature\Admin;

use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\WorkerProfile;
use App\Models\WorkerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRequestQueueTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    private function qualifiedWorker(ServiceCategory $category): User
    {
        $worker = User::factory()->worker()->create();
        WorkerProfile::factory()->for($worker, 'user')->verified()->create();
        WorkerService::create([
            'worker_id' => $worker->id,
            'service_category_id' => $category->id,
            'price_from' => 40,
            'is_active' => true,
        ]);

        return $worker;
    }

    public function test_unassigned_actionable_request_appears_in_the_unassigned_queue(): void
    {
        $unassigned = ServiceRequest::factory()->status('pending')->create(['worker_id' => null]);

        $this->actingAs($this->admin)
            ->get(route('admin.requests.index', ['queue' => 'unassigned']))
            ->assertOk()
            ->assertSee($unassigned->reference);
    }

    public function test_assigned_request_is_excluded_from_the_unassigned_queue(): void
    {
        $category = ServiceCategory::factory()->create();
        $worker = $this->qualifiedWorker($category);
        $assigned = ServiceRequest::factory()->status('pending')->create([
            'service_category_id' => $category->id,
            'worker_id' => $worker->id,
        ]);
        $unassigned = ServiceRequest::factory()->status('pending')->create(['worker_id' => null]);

        $this->actingAs($this->admin)
            ->get(route('admin.requests.index', ['queue' => 'unassigned']))
            ->assertOk()
            ->assertSee($unassigned->reference)
            ->assertDontSee($assigned->reference);
    }

    public function test_a_declined_request_returns_to_the_unassigned_queue(): void
    {
        $category = ServiceCategory::factory()->create();
        $worker = $this->qualifiedWorker($category);
        $request = ServiceRequest::factory()->status('pending')->create([
            'service_category_id' => $category->id,
            'worker_id' => $worker->id,
        ]);

        $this->actingAs($worker)->post(route('worker.jobs.decline', $request))->assertRedirect();

        $this->actingAs($this->admin)
            ->get(route('admin.requests.index', ['queue' => 'unassigned']))
            ->assertOk()
            ->assertSee($request->fresh()->reference);
        $this->assertNull($request->fresh()->worker_id);
        $this->assertSame('pending', $request->fresh()->status);
    }

    public function test_emergency_requests_are_prioritised_in_the_default_ordering(): void
    {
        $old = ServiceRequest::factory()->status('pending')->create([
            'is_emergency' => false,
            'created_at' => now()->subDays(5),
        ]);
        $emergency = ServiceRequest::factory()->status('pending')->create([
            'is_emergency' => true,
            'created_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.requests.index'))
            ->assertOk()
            ->assertSeeInOrder([$emergency->reference, $old->reference]);
    }

    public function test_emergency_queue_filters_to_emergency_requests(): void
    {
        $emergency = ServiceRequest::factory()->status('pending')->create(['is_emergency' => true]);
        $normal = ServiceRequest::factory()->status('pending')->create(['is_emergency' => false]);

        $this->actingAs($this->admin)
            ->get(route('admin.requests.index', ['queue' => 'emergency']))
            ->assertOk()
            ->assertSee($emergency->reference)
            ->assertDontSee($normal->reference);
    }

    public function test_stale_queue_surfaces_only_old_unassigned_requests(): void
    {
        $stale = ServiceRequest::factory()->status('pending')->create([
            'worker_id' => null,
            'created_at' => now()->subHours(48),
        ]);
        $fresh = ServiceRequest::factory()->status('pending')->create([
            'worker_id' => null,
            'created_at' => now()->subMinutes(10),
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.requests.index', ['queue' => 'stale']))
            ->assertOk()
            ->assertSee($stale->reference)
            ->assertDontSee($fresh->reference);
    }

    public function test_stale_threshold_is_configurable(): void
    {
        config(['sale7li.stale_request_hours' => 1]);
        $borderline = ServiceRequest::factory()->status('pending')->create([
            'worker_id' => null,
            'created_at' => now()->subHours(2),
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.requests.index', ['queue' => 'stale']))
            ->assertOk()
            ->assertSee($borderline->reference);
    }

    public function test_request_age_is_shown_in_the_queue(): void
    {
        $request = ServiceRequest::factory()->status('pending')->create([
            'created_at' => now()->subDays(3),
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.requests.index'))
            ->assertOk()
            ->assertSee($request->fresh()->created_at->diffForHumans());
    }

    public function test_status_filter_still_works(): void
    {
        $pending = ServiceRequest::factory()->status('pending')->create();
        $completed = ServiceRequest::factory()->status('completed')->create();

        $this->actingAs($this->admin)
            ->get(route('admin.requests.index', ['status' => 'completed']))
            ->assertOk()
            ->assertSee($completed->reference)
            ->assertDontSee($pending->reference);
    }

    public function test_non_admin_cannot_access_the_request_queue(): void
    {
        $this->get(route('admin.requests.index'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->customer()->create())
            ->get(route('admin.requests.index', ['queue' => 'stale']))->assertForbidden();
    }
}
