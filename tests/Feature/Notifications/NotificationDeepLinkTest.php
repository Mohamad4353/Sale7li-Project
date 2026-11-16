<?php

namespace Tests\Feature\Notifications;

use App\Models\Notification;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationDeepLinkTest extends TestCase
{
    use RefreshDatabase;

    private function notifyFor(User $user, ?ServiceRequest $request): Notification
    {
        return Notification::create([
            'user_id' => $user->id,
            'type' => 'request_status',
            'title' => 't',
            'message' => 'm',
            'data' => $request ? ['reference' => $request->reference, 'status' => 'accepted'] : null,
            'service_request_id' => $request?->id,
        ]);
    }

    public function test_customer_notification_links_to_the_customer_request_detail_and_marks_read(): void
    {
        $customer = User::factory()->customer()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('accepted')->create();
        $n = $this->notifyFor($customer, $request);

        $this->actingAs($customer)
            ->get(route('notifications.open', $n))
            ->assertRedirect(route('customer.requests.show', $request));

        $this->assertNotNull($n->fresh()->read_at);
    }

    public function test_worker_notification_links_to_the_worker_job_detail(): void
    {
        $worker = User::factory()->worker()->create();
        $request = ServiceRequest::factory()->status('accepted')->create(['worker_id' => $worker->id]);
        $n = $this->notifyFor($worker, $request);

        $this->actingAs($worker)
            ->get(route('notifications.open', $n))
            ->assertRedirect(route('worker.jobs.show', $request));
    }

    public function test_admin_notification_links_to_the_admin_request_detail(): void
    {
        $admin = User::factory()->admin()->create();
        $request = ServiceRequest::factory()->status('pending')->create();
        $n = $this->notifyFor($admin, $request);

        $this->actingAs($admin)
            ->get(route('notifications.open', $n))
            ->assertRedirect(route('admin.requests.show', $request));
    }

    public function test_notification_without_a_subject_redirects_back_to_the_list_without_a_broken_link(): void
    {
        $customer = User::factory()->customer()->create();
        $n = $this->notifyFor($customer, null);

        $this->actingAs($customer)
            ->get(route('notifications.open', $n))
            ->assertRedirect(route('notifications.index'));

        $this->assertNotNull($n->fresh()->read_at);
    }

    public function test_a_user_cannot_open_another_users_notification(): void
    {
        $owner = User::factory()->customer()->create();
        $stranger = User::factory()->customer()->create();
        $request = ServiceRequest::factory()->for($owner, 'customer')->status('accepted')->create();
        $n = $this->notifyFor($owner, $request);

        $this->actingAs($stranger)
            ->get(route('notifications.open', $n))
            ->assertNotFound();

        $this->assertNull($n->fresh()->read_at);
    }

    public function test_a_notification_pointing_at_an_unviewable_request_falls_back_to_the_list(): void
    {
        // A worker who was un-assigned still holds an old notification whose
        // service_request_id they may no longer view.
        $worker = User::factory()->worker()->create();
        $request = ServiceRequest::factory()->status('pending')->create(['worker_id' => null]);
        $n = $this->notifyFor($worker, $request);

        $this->actingAs($worker)
            ->get(route('notifications.open', $n))
            ->assertRedirect(route('notifications.index'));
    }

    public function test_index_rows_are_links_to_the_open_route(): void
    {
        $customer = User::factory()->customer()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('accepted')->create();
        $n = $this->notifyFor($customer, $request);

        $this->actingAs($customer)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('href="'.route('notifications.open', $n).'"', false);
    }

    public function test_guest_cannot_open_a_notification(): void
    {
        $customer = User::factory()->customer()->create();
        $n = $this->notifyFor($customer, null);

        $this->get(route('notifications.open', $n))->assertRedirect(route('login'));
    }
}
