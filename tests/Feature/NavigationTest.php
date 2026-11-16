<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use App\Models\WorkerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_marketing_navigation_only(): void
    {
        $response = $this->get('/');

        $response->assertSee(route('login'), false);
        $response->assertSee(route('register'), false);
        $response->assertDontSee('href="'.route('customer.dashboard').'"', false);
    }

    public function test_customer_sees_customer_navigation_and_not_worker_or_admin(): void
    {
        $customer = User::factory()->customer()->create();

        $response = $this->actingAs($customer)->get(route('customer.dashboard'));

        $response->assertSee('href="'.route('customer.requests.index').'"', false);
        $response->assertSee('href="'.route('customer.addresses.index').'"', false);
        $response->assertDontSee('href="'.route('worker.jobs.index').'"', false);
        $response->assertDontSee('href="'.route('admin.users.index').'"', false);
    }

    public function test_worker_sees_worker_navigation_and_not_customer_requests_or_admin(): void
    {
        $worker = User::factory()->worker()->create();
        WorkerProfile::factory()->for($worker, 'user')->create();

        $response = $this->actingAs($worker)->get(route('worker.dashboard'));

        $response->assertSee('href="'.route('worker.jobs.index').'"', false);
        $response->assertSee('href="'.route('worker.services.edit').'"', false);
        $response->assertSee('href="'.route('worker.availability.edit').'"', false);
        $response->assertSee('href="'.route('worker.profile.edit').'"', false);
        $response->assertDontSee('href="'.route('customer.requests.index').'"', false);
        $response->assertDontSee('href="'.route('admin.dashboard').'"', false);
    }

    public function test_worker_only_navigation_links_are_not_shown_to_other_roles(): void
    {
        $customer = User::factory()->customer()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($customer)->get(route('customer.dashboard'))
            ->assertDontSee('href="'.route('worker.services.edit').'"', false)
            ->assertDontSee('href="'.route('worker.availability.edit').'"', false);

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertDontSee('href="'.route('worker.services.edit').'"', false)
            ->assertDontSee('href="'.route('worker.availability.edit').'"', false);
    }

    public function test_admin_sees_admin_navigation(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertSee('href="'.route('admin.requests.index').'"', false);
        $response->assertSee('href="'.route('admin.complaints.index').'"', false);
        $response->assertSee('href="'.route('admin.users.index').'"', false);
        $response->assertSee('href="'.route('admin.services.index').'"', false);
        $response->assertDontSee('href="'.route('customer.dashboard').'"', false);
    }

    public function test_each_role_has_a_navigation_link_to_its_complaints_area(): void
    {
        $this->actingAs(User::factory()->customer()->create())->get(route('customer.dashboard'))
            ->assertSee('href="'.route('customer.complaints.index').'"', false);

        $worker = User::factory()->worker()->create();
        WorkerProfile::factory()->for($worker, 'user')->create();
        $this->actingAs($worker)->get(route('worker.dashboard'))
            ->assertSee('href="'.route('worker.complaints.index').'"', false);

        $this->actingAs(User::factory()->admin()->create())->get(route('admin.dashboard'))
            ->assertSee('href="'.route('admin.complaints.index').'"', false);
    }

    public function test_unread_notification_badge_appears_only_when_there_are_unread_notifications(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)->get(route('customer.dashboard'))
            ->assertDontSee('aria-label="'.__('nav.notifications').' (', false);

        Notification::create([
            'user_id' => $customer->id, 'type' => 'request_status',
            'title' => 't', 'message' => 'm',
        ]);

        $this->actingAs($customer)->get(route('customer.dashboard'))
            ->assertSee('aria-label="'.__('nav.notifications').' (1)"', false);
    }
}
