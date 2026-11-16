<?php

namespace Tests\Feature;

use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login_from_every_portal(): void
    {
        $this->get(route('customer.dashboard'))->assertRedirect(route('login'));
        $this->get(route('worker.dashboard'))->assertRedirect(route('login'));
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_customer_cannot_reach_worker_or_admin_areas(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)->get(route('worker.dashboard'))->assertForbidden();
        $this->actingAs($customer)->get(route('worker.jobs.index'))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_worker_cannot_reach_customer_or_admin_areas(): void
    {
        $worker = User::factory()->worker()->create();

        $this->actingAs($worker)->get(route('customer.dashboard'))->assertForbidden();
        $this->actingAs($worker)->get(route('customer.requests.index'))->assertForbidden();
        $this->actingAs($worker)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_admin_cannot_reach_customer_or_worker_portals(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('customer.dashboard'))->assertForbidden();
        $this->actingAs($admin)->get(route('worker.dashboard'))->assertForbidden();
    }

    public function test_suspended_user_is_blocked_from_their_portal(): void
    {
        // A suspended account is force-logged-out on its next request (see
        // EnsureAccountActive) rather than left with a live 403'd session.
        $customer = User::factory()->customer()->suspended()->create();

        $this->actingAs($customer)->get(route('customer.dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_customer_cannot_view_another_customers_request(): void
    {
        $owner = User::factory()->customer()->create();
        $other = User::factory()->customer()->create();
        $request = ServiceRequest::factory()->for($owner, 'customer')->create();

        $this->actingAs($other)->get(route('customer.requests.show', $request))->assertForbidden();
    }

    public function test_worker_cannot_view_a_job_not_assigned_to_them(): void
    {
        $assigned = User::factory()->worker()->create();
        $other = User::factory()->worker()->create();
        $request = ServiceRequest::factory()->status('accepted')->create(['worker_id' => $assigned->id]);

        $this->actingAs($other)->get(route('worker.jobs.show', $request))->assertForbidden();
    }

    public function test_customer_cannot_open_a_conversation_for_a_request_they_do_not_own(): void
    {
        $owner = User::factory()->customer()->create();
        $worker = User::factory()->worker()->create();
        $other = User::factory()->customer()->create();
        $request = ServiceRequest::factory()->for($owner, 'customer')->status('accepted')->create([
            'worker_id' => $worker->id,
        ]);

        $this->actingAs($other)->get(route('customer.messages.show', $request))->assertForbidden();
    }
}
