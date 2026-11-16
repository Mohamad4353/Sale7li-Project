<?php

namespace Tests\Feature\Security;

use App\Models\Address;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\WorkerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MassAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_cannot_create_an_admin_account(): void
    {
        $this->from('/register')->post('/register', [
            'name' => 'Sneaky',
            'email' => 'sneaky@example.com',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
            'role' => 'admin',
        ])->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'sneaky@example.com']);
    }

    public function test_registration_ignores_injected_privileged_columns(): void
    {
        $this->post('/register', [
            'name' => 'Normal',
            'email' => 'normal@example.com',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
            'role' => 'customer',
            'status' => 'suspended',
            'id' => 999999,
            'email_verified_at' => now(),
        ])->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'normal@example.com')->firstOrFail();
        $this->assertSame('active', $user->status);
        $this->assertNotSame(999999, $user->id);
        // An injected email_verified_at must not skip verification.
        $this->assertNull($user->email_verified_at);
    }

    public function test_worker_cannot_self_verify_or_inflate_reputation_via_profile_update(): void
    {
        $worker = User::factory()->worker()->create();
        $profile = WorkerProfile::factory()->for($worker, 'user')->create([
            'verification_status' => 'pending',
            'rating_average' => 0,
            'completed_jobs' => 0,
        ]);

        $this->actingAs($worker)->put(route('worker.profile.update'), [
            'name' => 'Worker',
            'experience_years' => 5,
            'service_radius_km' => 20,
            'verification_status' => 'verified',
            'rating_average' => 5,
            'rating_count' => 500,
            'completed_jobs' => 999,
            'cancellation_rate' => 0,
        ])->assertRedirect();

        $profile->refresh();
        $this->assertSame('pending', $profile->verification_status);
        $this->assertEqualsWithDelta(0.0, (float) $profile->rating_average, 0.001);
        $this->assertSame(0, $profile->completed_jobs);
    }

    public function test_booking_ignores_injected_status_payment_and_ownership_fields(): void
    {
        $customer = User::factory()->customer()->create();
        $otherCustomer = User::factory()->customer()->create();
        $someWorker = User::factory()->worker()->create();
        $category = ServiceCategory::factory()->create();
        $address = Address::factory()->for($customer, 'user')->create();

        $this->actingAs($customer)->post(route('customer.booking.store'), [
            'service_category_id' => $category->id,
            'problem_description' => 'Broken thing.',
            'address_id' => $address->id,
            'preferred_date' => now()->addDay()->toDateString(),
            'preferred_time' => '10:00',
            'status' => 'completed',
            'payment_status' => 'paid',
            'worker_id' => $someWorker->id,
            'customer_id' => $otherCustomer->id,
            'quoted_total' => 1,
            'final_total' => 1,
        ])->assertRedirect();

        $request = ServiceRequest::firstOrFail();
        $this->assertSame($customer->id, $request->customer_id);
        $this->assertSame('pending', $request->status);
        $this->assertSame('pending', $request->payment_status);
        $this->assertNull($request->worker_id);
        $this->assertNull($request->quoted_total);
        $this->assertNull($request->final_total);
    }
}
