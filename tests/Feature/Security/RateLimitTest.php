<?php

namespace Tests\Feature\Security;

use App\Models\Address;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_is_rate_limited_after_repeated_failures(): void
    {
        User::factory()->create(['email' => 'victim@example.com', 'password' => 'Password@123']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'victim@example.com', 'password' => 'wrong'])
                ->assertStatus(302);
        }

        $this->post('/login', ['email' => 'victim@example.com', 'password' => 'wrong'])
            ->assertStatus(429);
    }

    public function test_login_lockout_is_scoped_per_email_and_does_not_lock_other_accounts(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->post('/login', ['email' => 'target@example.com', 'password' => 'wrong']);
        }

        // A different email from the same client is still within its own bucket.
        $this->post('/login', ['email' => 'someone-else@example.com', 'password' => 'wrong'])
            ->assertStatus(302);
    }

    public function test_booking_submissions_are_rate_limited(): void
    {
        $customer = User::factory()->customer()->create();
        $category = ServiceCategory::factory()->create();
        $address = Address::factory()->for($customer, 'user')->create();
        $this->actingAs($customer);

        $payload = [
            'service_category_id' => $category->id,
            'problem_description' => 'Something is broken and needs a look.',
            'address_id' => $address->id,
            'preferred_date' => now()->addDay()->toDateString(),
            'preferred_time' => '10:00',
        ];

        for ($i = 0; $i < 12; $i++) {
            $this->post(route('customer.booking.store'), $payload)->assertStatus(302);
        }

        $this->post(route('customer.booking.store'), $payload)->assertStatus(429);
    }
}
