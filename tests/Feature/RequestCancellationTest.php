<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestCancellationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_cancel_a_pending_request(): void
    {
        $customer = User::factory()->customer()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('pending')->create();

        $this->actingAs($customer)
            ->post(route('customer.requests.cancel', $request))
            ->assertRedirect();

        $this->assertSame('cancelled', $request->fresh()->status);
    }

    public function test_customer_cannot_cancel_an_in_progress_request_and_gets_no_server_error(): void
    {
        $customer = User::factory()->customer()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('in_progress')->create();

        $response = $this->from(route('customer.requests.show', $request))
            ->actingAs($customer)
            ->post(route('customer.requests.cancel', $request));

        $response->assertRedirect(route('customer.requests.show', $request));
        $response->assertSessionHasErrors();
        $this->assertSame('in_progress', $request->fresh()->status);
    }

    public function test_customer_cannot_cancel_another_customers_request(): void
    {
        $owner = User::factory()->customer()->create();
        $other = User::factory()->customer()->create();
        $request = ServiceRequest::factory()->for($owner, 'customer')->status('pending')->create();

        $this->actingAs($other)
            ->post(route('customer.requests.cancel', $request))
            ->assertForbidden();

        $this->assertSame('pending', $request->fresh()->status);
    }

    /**
     * ATTACK POINT #2B: a cancelled request must not leave a live-looking
     * Appointment behind. RequestWorkflow itself is untouched — the mirroring
     * happens in the controller, atomically with the status transition.
     */
    public function test_cancelling_a_scheduled_request_also_cancels_its_appointment(): void
    {
        $customer = User::factory()->customer()->create();
        $worker = User::factory()->worker()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('scheduled')->create([
            'worker_id' => $worker->id,
        ]);
        $appointment = Appointment::create([
            'service_request_id' => $request->id,
            'worker_id' => $worker->id,
            'scheduled_date' => now()->addDays(2)->toDateString(),
            'scheduled_time' => '10:00',
            'status' => 'scheduled',
        ]);

        $this->actingAs($customer)->post(route('customer.requests.cancel', $request))->assertRedirect();

        $this->assertSame('cancelled', $request->fresh()->status);
        $this->assertSame('cancelled', $appointment->fresh()->status);
        // The date/time themselves are historical data — left untouched.
        $this->assertSame('10:00', $appointment->fresh()->scheduled_time);
    }

    public function test_cancelling_a_rescheduled_appointment_also_marks_it_cancelled(): void
    {
        $customer = User::factory()->customer()->create();
        $worker = User::factory()->worker()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('scheduled')->create([
            'worker_id' => $worker->id,
        ]);
        $appointment = Appointment::create([
            'service_request_id' => $request->id,
            'worker_id' => $worker->id,
            'scheduled_date' => now()->addDays(2)->toDateString(),
            'scheduled_time' => '10:00',
            'status' => 'rescheduled',
        ]);

        $this->actingAs($customer)->post(route('customer.requests.cancel', $request))->assertRedirect();

        $this->assertSame('cancelled', $appointment->fresh()->status);
    }

    public function test_cancelling_a_request_with_no_appointment_still_works(): void
    {
        $customer = User::factory()->customer()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('accepted')->create();

        $this->actingAs($customer)->post(route('customer.requests.cancel', $request))->assertRedirect();

        $this->assertSame('cancelled', $request->fresh()->status);
    }

    public function test_preferred_date_and_time_are_unaffected_by_cancellation(): void
    {
        $customer = User::factory()->customer()->create();
        $worker = User::factory()->worker()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('scheduled')->create([
            'worker_id' => $worker->id,
            'preferred_date' => now()->addDay()->toDateString(),
            'preferred_time' => '09:00',
        ]);

        $this->actingAs($customer)->post(route('customer.requests.cancel', $request))->assertRedirect();

        $fresh = $request->fresh();
        $this->assertSame(now()->addDay()->toDateString(), $fresh->preferred_date->toDateString());
        $this->assertSame('09:00', $fresh->preferred_time);
    }
}
