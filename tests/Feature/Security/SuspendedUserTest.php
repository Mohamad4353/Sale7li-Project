<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuspendedUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_user_keeps_access_to_shared_authenticated_routes(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)->get('/notifications')->assertOk();
        $this->assertAuthenticated();
    }

    public function test_user_suspended_mid_session_is_locked_out_of_shared_routes(): void
    {
        $customer = User::factory()->customer()->create();
        $this->actingAs($customer);

        $customer->update(['status' => 'suspended']);

        $response = $this->get('/notifications');

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_user_suspended_mid_session_is_locked_out_of_their_portal(): void
    {
        $worker = User::factory()->worker()->create();
        $this->actingAs($worker);

        $worker->update(['status' => 'suspended']);

        $this->get(route('worker.dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_suspended_user_cannot_reach_the_generic_dashboard(): void
    {
        $customer = User::factory()->customer()->create();
        $this->actingAs($customer);
        $customer->update(['status' => 'suspended']);

        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
