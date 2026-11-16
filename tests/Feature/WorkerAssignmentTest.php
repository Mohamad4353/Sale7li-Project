<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\WorkerProfile;
use App\Models\WorkerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkerAssignmentTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_admin_assignment_leaves_request_pending_and_notifies_the_worker(): void
    {
        $admin = User::factory()->admin()->create();
        $category = ServiceCategory::factory()->create();
        $worker = $this->qualifiedWorker($category);
        $request = ServiceRequest::factory()->status('pending')->create([
            'service_category_id' => $category->id,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.requests.assign', $request), ['worker_id' => $worker->id])
            ->assertRedirect();

        $request->refresh();
        $this->assertSame($worker->id, $request->worker_id);
        $this->assertSame('pending', $request->status, 'Assignment must not auto-accept the job.');
        $this->assertTrue(
            Notification::where('user_id', $worker->id)->where('type', 'request_assigned')->exists()
        );
    }

    public function test_admin_cannot_assign_an_unqualified_worker(): void
    {
        $admin = User::factory()->admin()->create();
        $category = ServiceCategory::factory()->create();
        $worker = User::factory()->worker()->create();
        WorkerProfile::factory()->for($worker, 'user')->create(); // pending, no services
        $request = ServiceRequest::factory()->status('pending')->create([
            'service_category_id' => $category->id,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.requests.assign', $request), ['worker_id' => $worker->id])
            ->assertSessionHasErrors('worker_id');

        $this->assertNull($request->fresh()->worker_id);
    }

    public function test_assigned_worker_can_accept_and_request_becomes_accepted(): void
    {
        $category = ServiceCategory::factory()->create();
        $worker = $this->qualifiedWorker($category);
        $request = ServiceRequest::factory()->status('pending')->create([
            'service_category_id' => $category->id,
            'worker_id' => $worker->id,
        ]);

        $this->actingAs($worker)
            ->post(route('worker.jobs.accept', $request))
            ->assertRedirect();

        $this->assertSame('accepted', $request->fresh()->status);
        $this->assertTrue(
            Notification::where('user_id', $request->customer_id)->where('type', 'request_status')->exists()
        );
    }

    public function test_assigned_worker_can_decline_and_request_returns_to_unassigned_pending(): void
    {
        $category = ServiceCategory::factory()->create();
        $worker = $this->qualifiedWorker($category);
        $request = ServiceRequest::factory()->status('pending')->create([
            'service_category_id' => $category->id,
            'worker_id' => $worker->id,
        ]);

        $this->actingAs($worker)
            ->post(route('worker.jobs.decline', $request))
            ->assertRedirect();

        $request->refresh();
        $this->assertNull($request->worker_id);
        $this->assertSame('pending', $request->status);
    }

    public function test_worker_cannot_accept_a_job_assigned_to_someone_else(): void
    {
        $category = ServiceCategory::factory()->create();
        $assigned = $this->qualifiedWorker($category);
        $intruder = $this->qualifiedWorker($category);
        $request = ServiceRequest::factory()->status('pending')->create([
            'service_category_id' => $category->id,
            'worker_id' => $assigned->id,
        ]);

        $this->actingAs($intruder)
            ->post(route('worker.jobs.accept', $request))
            ->assertForbidden();

        $this->assertSame('pending', $request->fresh()->status);
    }

    public function test_worker_cannot_accept_a_request_that_is_already_accepted(): void
    {
        $category = ServiceCategory::factory()->create();
        $worker = $this->qualifiedWorker($category);
        $request = ServiceRequest::factory()->status('accepted')->create([
            'service_category_id' => $category->id,
            'worker_id' => $worker->id,
        ]);

        $this->from(route('worker.jobs.show', $request))
            ->actingAs($worker)
            ->post(route('worker.jobs.accept', $request))
            ->assertSessionHasErrors();

        $this->assertSame('accepted', $request->fresh()->status);
    }
}
