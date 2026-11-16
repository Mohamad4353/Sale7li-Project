<?php

namespace Tests\Feature\Admin;

use App\Models\Address;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\WorkerProfile;
use App\Models\WorkerService;
use App\Services\RequestWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminRequestDetailTest extends TestCase
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

    public function test_admin_can_view_the_request_detail_page(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->customer()->create(['name' => 'Dana Customer']);
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('pending')->create([
            'problem_description' => 'The bathroom tap is dripping badly.',
            'is_emergency' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.requests.show', $request))
            ->assertOk()
            ->assertSee($request->reference)
            ->assertSee('Dana Customer')
            ->assertSee('The bathroom tap is dripping badly.');
    }

    public function test_non_admins_cannot_access_the_admin_request_detail_page(): void
    {
        $request = ServiceRequest::factory()->status('pending')->create();

        $this->get(route('admin.requests.show', $request))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->customer()->create())
            ->get(route('admin.requests.show', $request))->assertForbidden();

        $this->actingAs(User::factory()->worker()->create())
            ->get(route('admin.requests.show', $request))->assertForbidden();
    }

    public function test_admin_can_stream_a_private_request_attachment_from_the_detail_context(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('requests/photo.jpg', 'binary');

        $admin = User::factory()->admin()->create();
        $request = ServiceRequest::factory()->status('pending')->create([
            'problem_images' => ['requests/photo.jpg'],
        ]);

        $this->actingAs($admin)
            ->get(route('attachments.request', [$request, 'problem', 0]))
            ->assertOk();

        // A stranger still cannot.
        $this->actingAs(User::factory()->customer()->create())
            ->get(route('attachments.request', [$request, 'problem', 0]))
            ->assertForbidden();
    }

    public function test_detail_page_never_leaks_a_storage_path(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('requests/photo.jpg', 'binary');
        $admin = User::factory()->admin()->create();
        $request = ServiceRequest::factory()->status('pending')->create([
            'problem_images' => ['requests/photo.jpg'],
        ]);

        $content = $this->actingAs($admin)->get(route('admin.requests.show', $request))->getContent();

        $this->assertStringNotContainsString('requests/photo.jpg', $content);
        $this->assertStringNotContainsString('storage/app', $content);
    }

    public function test_assignment_from_the_detail_page_still_enforces_worker_eligibility(): void
    {
        $admin = User::factory()->admin()->create();
        $category = ServiceCategory::factory()->create();
        $unqualified = User::factory()->worker()->create();
        WorkerProfile::factory()->for($unqualified, 'user')->create(); // pending, no services
        $request = ServiceRequest::factory()->status('pending')->create([
            'service_category_id' => $category->id,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.requests.show', $request))
            ->post(route('admin.requests.assign', $request), ['worker_id' => $unqualified->id])
            ->assertSessionHasErrors('worker_id');

        $this->assertNull($request->fresh()->worker_id);

        // A qualified worker still assigns fine (and stays pending).
        $qualified = $this->qualifiedWorker($category);
        $this->actingAs($admin)
            ->post(route('admin.requests.assign', $request), ['worker_id' => $qualified->id])
            ->assertRedirect();
        $request->refresh();
        $this->assertSame($qualified->id, $request->worker_id);
        $this->assertSame('pending', $request->status);
    }

    public function test_activity_feed_renders_readable_labels_not_raw_action_codes(): void
    {
        $admin = User::factory()->admin()->create();
        $category = ServiceCategory::factory()->create();
        $worker = $this->qualifiedWorker($category);
        $customer = User::factory()->customer()->create();
        $address = Address::factory()->create(['user_id' => $customer->id]);

        $workflow = app(RequestWorkflow::class);
        $request = $workflow->create([
            'customer_id' => $customer->id,
            'service_category_id' => $category->id,
            'address_id' => $address->id,
            'problem_description' => 'Outlet sparks when plugged in.',
            'preferred_date' => now()->addDay()->toDateString(),
            'preferred_time' => '10:00',
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);
        $workflow->assign($request, $worker);
        $request = $workflow->workerAccept($request->fresh(), $worker);
        $request = $workflow->transition($request, 'scheduled');
        $request = $workflow->transition($request, 'on_the_way');

        $html = $this->actingAs($admin)
            ->get(route('admin.requests.show', $request))
            ->assertOk()
            ->getContent();

        // The raw dotted ActivityLog codes must never surface to the admin.
        $this->assertStringNotContainsString('request.created', $html);
        $this->assertStringNotContainsString('request.assigned', $html);
        $this->assertStringNotContainsString('request.status_changed', $html);

        // They render as human-readable labels, and a status change names the
        // state it moved to (from the row's own persisted metadata).
        $this->assertStringContainsString(__('activity.request.created'), $html);
        $this->assertStringContainsString(__('activity.request.assigned'), $html);
        $this->assertStringContainsString(
            __('activity.status_changed_to', ['status' => __('status.request.on_the_way')]),
            $html,
        );
    }

    public function test_detail_page_shows_quotations_payment_and_timeline_context(): void
    {
        $admin = User::factory()->admin()->create();
        $category = ServiceCategory::factory()->create();
        $worker = $this->qualifiedWorker($category);
        $request = ServiceRequest::factory()->status('accepted')->create([
            'service_category_id' => $category->id,
            'worker_id' => $worker->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.requests.show', $request))
            ->assertOk()
            ->assertSee($worker->name)
            ->assertSee(__('requests.timeline'));
    }

    public function test_the_admin_detail_still_renders_the_shared_status_timeline(): void
    {
        // The admin page mounts <x-request-timeline> with only :status (no
        // history / worker props) — it must render on the component defaults.
        $admin = User::factory()->admin()->create();
        $request = ServiceRequest::factory()->status('scheduled')->create();

        $this->actingAs($admin)
            ->get(route('admin.requests.show', $request))
            ->assertOk()
            ->assertSee(__('requests.steps.submitted'))
            ->assertSee(__('requests.steps.scheduled'))
            ->assertSee(__('requests.steps.completed'))
            ->assertDontSee('requests.steps.');
    }
}
