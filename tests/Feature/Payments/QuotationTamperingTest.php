<?php

namespace Tests\Feature\Payments;

use App\Models\Quotation;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Parameter-tampering coverage for the quote workflow, beyond the posted-`total`
 * and posted-`status` cases already in QuotationImmutabilityTest: a worker
 * cannot rebind the quote's request or its attribution, only the assigned
 * worker may quote at all, and accepting one quote closes the siblings.
 * Nothing financial changes here.
 */
class QuotationTamperingTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: User, 2: ServiceRequest} */
    private function assignedJob(string $status = 'accepted'): array
    {
        $customer = User::factory()->customer()->create();
        $worker = User::factory()->worker()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status($status)->create([
            'worker_id' => $worker->id,
        ]);

        return [$customer, $worker, $request];
    }

    public function test_the_worker_quote_is_bound_to_the_route_request_and_the_acting_worker(): void
    {
        [, $worker, $request] = $this->assignedJob();
        $victimWorker = User::factory()->worker()->create();
        $foreignRequest = ServiceRequest::factory()->status('accepted')->create();

        $this->actingAs($worker)->post(route('worker.jobs.quotation', $request), [
            'base_price' => 80,
            // every one of these is off-contract and must be dropped
            'worker_id' => $victimWorker->id,
            'service_request_id' => $foreignRequest->id,
            'customer_id' => 999,
            'id' => 999,
            'status' => 'accepted',
        ])->assertRedirect();

        $quote = Quotation::sole();
        $this->assertSame($worker->id, $quote->worker_id);
        $this->assertSame($request->id, $quote->service_request_id);
        $this->assertSame('pending', $quote->status);
        $this->assertSame('80.00', $quote->total);
        // The foreign request never got a quotation.
        $this->assertSame(0, $foreignRequest->quotations()->count());
    }

    public function test_only_the_assigned_worker_may_submit_a_quotation(): void
    {
        [$customer, , $request] = $this->assignedJob();
        $intruder = User::factory()->worker()->create();

        $this->actingAs($intruder)->post(route('worker.jobs.quotation', $request), ['base_price' => 50])
            ->assertForbidden();

        $this->actingAs($customer)->post(route('worker.jobs.quotation', $request), ['base_price' => 50])
            ->assertForbidden();

        $this->assertSame(0, Quotation::count());
    }

    public function test_a_guest_cannot_submit_a_quotation(): void
    {
        [, , $request] = $this->assignedJob();

        $this->post(route('worker.jobs.quotation', $request), ['base_price' => 50])
            ->assertRedirect(route('login'));

        $this->assertSame(0, Quotation::count());
    }

    public function test_accepting_one_quote_closes_the_other_pending_quotes_on_the_request(): void
    {
        [$customer, $worker, $request] = $this->assignedJob();

        $chosen = Quotation::factory()->create([
            'service_request_id' => $request->id, 'worker_id' => $worker->id,
            'total' => 120, 'status' => 'pending',
        ]);
        $other = Quotation::factory()->create([
            'service_request_id' => $request->id, 'worker_id' => $worker->id,
            'total' => 300, 'status' => 'pending',
        ]);

        $this->actingAs($customer)->post(route('customer.quotations.accept', $chosen))->assertRedirect();

        $this->assertSame('accepted', $chosen->fresh()->status);
        $this->assertSame('rejected', $other->fresh()->status);
        $this->assertNotNull($other->fresh()->responded_at);
        $this->assertSame('120.00', $request->fresh()->quoted_total); // the accepted total, not 300
    }
}
