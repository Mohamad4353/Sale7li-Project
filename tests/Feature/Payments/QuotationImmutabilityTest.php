<?php

namespace Tests\Feature\Payments;

use App\Models\Quotation;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotationImmutabilityTest extends TestCase
{
    use BuildsPaidJobs, RefreshDatabase;

    public function test_a_customer_cannot_accept_a_quotation_after_payment(): void
    {
        ['customer' => $customer, 'worker' => $worker, 'request' => $request, 'quotation' => $accepted]
            = $this->quotedCompletedJob(['base_price' => 100]);

        $this->actingAs($customer)->post(route('customer.payments.pay', $request))->assertRedirect();

        // a second pending quotation sneaked onto the request row directly
        $other = Quotation::factory()->create([
            'service_request_id' => $request->id,
            'worker_id' => $worker->id,
            'total' => 999,
            'status' => 'pending',
        ]);

        $this->from(route('customer.requests.show', $request))
            ->actingAs($customer)
            ->post(route('customer.quotations.accept', $other))
            ->assertSessionHasErrors('quotation');

        $this->assertSame('pending', $other->fresh()->status);
        $this->assertSame('accepted', $accepted->fresh()->status);
        $this->assertSame('100.00', $request->fresh()->quoted_total);
        $this->assertSame('100.00', $request->fresh()->final_total);
    }

    public function test_a_worker_cannot_submit_a_second_quotation_once_one_is_accepted(): void
    {
        ['worker' => $worker, 'request' => $request] = $this->quotedAcceptedJob(['base_price' => 100]);

        $this->from(route('worker.jobs.show', $request))
            ->actingAs($worker)
            ->post(route('worker.jobs.quotation', $request), ['base_price' => 50])
            ->assertSessionHasErrors('quotation');

        $this->assertSame(1, Quotation::where('service_request_id', $request->id)->count());
        $this->assertSame('100.00', $request->fresh()->quoted_total);
    }

    public function test_a_worker_cannot_submit_a_quotation_after_payment(): void
    {
        ['customer' => $customer, 'worker' => $worker, 'request' => $request] = $this->quotedCompletedJob(['base_price' => 100]);
        $this->actingAs($customer)->post(route('customer.payments.pay', $request))->assertRedirect();

        $this->from(route('worker.jobs.show', $request))
            ->actingAs($worker)
            ->post(route('worker.jobs.quotation', $request), ['base_price' => 5])
            ->assertSessionHasErrors('quotation');

        $this->assertSame(1, Quotation::where('service_request_id', $request->id)->count());
    }

    public function test_the_first_accepted_quotation_stays_authoritative(): void
    {
        ['worker' => $worker, 'request' => $request, 'quotation' => $first] = $this->quotedAcceptedJob(['base_price' => 120]);

        // worker tries again, customer never gets a second quote to accept
        $this->actingAs($worker)
            ->post(route('worker.jobs.quotation', $request), ['base_price' => 40])
            ->assertSessionHasErrors('quotation');

        $this->assertSame('accepted', $first->fresh()->status);
        $this->assertSame('120.00', $first->fresh()->total);
        $this->assertSame('120.00', $request->fresh()->quoted_total);
    }

    public function test_a_foreign_customer_still_cannot_respond_to_a_quotation(): void
    {
        ['quotation' => $quotation] = $this->quotedAcceptedJob(['base_price' => 100]);
        $intruder = User::factory()->customer()->create();

        $this->actingAs($intruder)->post(route('customer.quotations.accept', $quotation))->assertForbidden();
        $this->actingAs($intruder)->post(route('customer.quotations.reject', $quotation))->assertForbidden();
    }

    public function test_quotation_total_is_still_server_computed_and_ignores_a_posted_total(): void
    {
        $customer = User::factory()->customer()->create();
        $worker = User::factory()->worker()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('accepted')->create([
            'worker_id' => $worker->id,
        ]);

        $this->actingAs($worker)->post(route('worker.jobs.quotation', $request), [
            'base_price' => 100,
            'additional_charges' => 20,
            'discount' => 10,
            'tax' => 5,
            'total' => 1,          // malicious
            'status' => 'accepted', // malicious
        ])->assertRedirect();

        $quotation = Quotation::where('service_request_id', $request->id)->firstOrFail();
        $this->assertSame('115.00', $quotation->total);
        $this->assertSame('pending', $quotation->status);
    }
}
