<?php

namespace Tests\Feature\Security;

use App\Models\Quotation;
use App\Models\Review;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\WorkerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StateTransitionSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_cannot_drive_the_worker_job_state_machine(): void
    {
        $customer = User::factory()->customer()->create();
        $worker = User::factory()->worker()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('accepted')->create([
            'worker_id' => $worker->id,
        ]);

        $this->actingAs($customer);
        $this->post(route('worker.jobs.status', $request), ['status' => 'completed'])->assertForbidden();
        $this->post(route('worker.jobs.accept', $request))->assertForbidden();
        $this->post(route('worker.jobs.quotation', $request), ['base_price' => 10])->assertForbidden();

        $this->assertSame('accepted', $request->fresh()->status);
    }

    public function test_worker_cannot_use_customer_money_and_review_actions(): void
    {
        $customer = User::factory()->customer()->create();
        $worker = User::factory()->worker()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('completed')->create([
            'worker_id' => $worker->id,
        ]);
        $quotation = Quotation::factory()->create([
            'service_request_id' => $request->id,
            'worker_id' => $worker->id,
        ]);

        $this->actingAs($worker);
        $this->post(route('customer.payments.pay', $request))->assertForbidden();
        $this->post(route('customer.quotations.accept', $quotation))->assertForbidden();
        $this->post(route('customer.reviews.store', $request), ['rating' => 5])->assertForbidden();
        $this->post(route('customer.requests.cancel', $request))->assertForbidden();
    }

    public function test_worker_cannot_accept_a_job_before_assignment(): void
    {
        $worker = User::factory()->worker()->create();
        $request = ServiceRequest::factory()->status('pending')->create(['worker_id' => null]);

        $this->actingAs($worker)
            ->post(route('worker.jobs.accept', $request))
            ->assertForbidden();
    }

    public function test_worker_cannot_skip_states_to_completed(): void
    {
        $worker = User::factory()->worker()->create();
        $request = ServiceRequest::factory()->status('accepted')->create(['worker_id' => $worker->id]);

        $this->from(route('worker.jobs.show', $request))
            ->actingAs($worker)
            ->post(route('worker.jobs.status', $request), ['status' => 'completed'])
            ->assertSessionHasErrors('status');

        $this->assertSame('accepted', $request->fresh()->status);
    }

    public function test_a_completed_job_can_only_be_reviewed_once(): void
    {
        $customer = User::factory()->customer()->create();
        $worker = User::factory()->worker()->create();
        WorkerProfile::factory()->for($worker, 'user')->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('completed')->create([
            'worker_id' => $worker->id,
        ]);

        $this->actingAs($customer)
            ->post(route('customer.reviews.store', $request), ['rating' => 5])
            ->assertRedirect();

        $this->from(route('customer.requests.show', $request))
            ->actingAs($customer)
            ->post(route('customer.reviews.store', $request), ['rating' => 1])
            ->assertSessionHasErrors();

        $this->assertSame(1, Review::where('service_request_id', $request->id)->count());
    }

    public function test_customer_cannot_review_a_job_that_is_not_completed(): void
    {
        $customer = User::factory()->customer()->create();
        $worker = User::factory()->worker()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('in_progress')->create([
            'worker_id' => $worker->id,
        ]);

        $this->from(route('customer.requests.show', $request))
            ->actingAs($customer)
            ->post(route('customer.reviews.store', $request), ['rating' => 5])
            ->assertSessionHasErrors();

        $this->assertDatabaseCount('reviews', 0);
    }
}
