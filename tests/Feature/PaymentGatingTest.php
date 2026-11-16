<?php

namespace Tests\Feature;

use App\Models\Quotation;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentGatingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: User, 2: ServiceRequest}
     */
    private function acceptedJobWithSubmittedQuote(): array
    {
        $category = ServiceCategory::factory()->create();
        $customer = User::factory()->customer()->create();
        $worker = User::factory()->worker()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('accepted')->create([
            'service_category_id' => $category->id,
            'worker_id' => $worker->id,
        ]);

        $this->actingAs($worker)->post(route('worker.jobs.quotation', $request), [
            'base_price' => 120,
        ])->assertRedirect();

        return [$customer, $worker, $request];
    }

    public function test_submitting_a_quotation_does_not_make_the_request_payable(): void
    {
        [$customer, , $request] = $this->acceptedJobWithSubmittedQuote();

        $this->assertNull($request->fresh()->quoted_total);

        $response = $this->from(route('customer.requests.show', $request))
            ->actingAs($customer)
            ->post(route('customer.payments.pay', $request));

        $response->assertSessionHasErrors();
        $this->assertDatabaseCount('payments', 0);
        $this->assertSame('pending', $request->fresh()->payment_status);
    }

    public function test_customer_can_pay_after_accepting_a_quotation(): void
    {
        [$customer, , $request] = $this->acceptedJobWithSubmittedQuote();
        $quotation = Quotation::where('service_request_id', $request->id)->firstOrFail();

        $this->actingAs($customer)
            ->post(route('customer.quotations.accept', $quotation))
            ->assertRedirect();

        $this->assertEqualsWithDelta(120, (float) $request->fresh()->quoted_total, 0.001);

        $this->actingAs($customer)
            ->post(route('customer.payments.pay', $request))
            ->assertRedirect();

        $request->refresh();
        $this->assertSame('paid', $request->payment_status);
        $this->assertDatabaseHas('payments', [
            'service_request_id' => $request->id,
            'status' => 'paid',
        ]);
        $this->assertDatabaseHas('invoices', ['service_request_id' => $request->id]);
    }

    public function test_customer_cannot_pay_another_customers_request(): void
    {
        [, , $request] = $this->acceptedJobWithSubmittedQuote();
        $quotation = Quotation::where('service_request_id', $request->id)->firstOrFail();
        $quotation->update(['status' => 'accepted']);
        $request->update(['quoted_total' => 120]);

        $intruder = User::factory()->customer()->create();

        $this->actingAs($intruder)
            ->post(route('customer.payments.pay', $request))
            ->assertForbidden();

        $this->assertDatabaseCount('payments', 0);
    }
}
