<?php

namespace Tests\Feature\Security;

use App\Models\Payment;
use App\Models\Quotation;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTamperingTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: ServiceRequest} */
    private function requestWithAcceptedQuote(float $total = 500): array
    {
        $customer = User::factory()->customer()->create();
        $worker = User::factory()->worker()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('in_progress')->create([
            'service_category_id' => ServiceCategory::factory()->create()->id,
            'worker_id' => $worker->id,
        ]);
        Quotation::factory()->create([
            'service_request_id' => $request->id,
            'worker_id' => $worker->id,
            'total' => $total,
            'status' => 'accepted',
        ]);
        $request->update(['quoted_total' => $total]);

        return [$customer, $request];
    }

    public function test_payment_amount_is_server_derived_and_ignores_request_input(): void
    {
        [$customer, $request] = $this->requestWithAcceptedQuote(500);

        $this->actingAs($customer)
            ->post(route('customer.payments.pay', $request), ['amount' => 1, 'total' => 1, 'currency' => 'XXX'])
            ->assertRedirect();

        $payment = Payment::where('service_request_id', $request->id)->firstOrFail();
        $this->assertEqualsWithDelta(500.0, (float) $payment->amount, 0.001);
        $this->assertSame('USD', $payment->currency);
    }

    public function test_cannot_pay_before_a_quotation_is_accepted(): void
    {
        $customer = User::factory()->customer()->create();
        $worker = User::factory()->worker()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('accepted')->create([
            'worker_id' => $worker->id,
        ]);
        Quotation::factory()->create([
            'service_request_id' => $request->id,
            'worker_id' => $worker->id,
            'status' => 'pending',
        ]);

        $this->from(route('customer.requests.show', $request))
            ->actingAs($customer)
            ->post(route('customer.payments.pay', $request))
            ->assertSessionHasErrors();

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_cannot_pay_another_customers_request(): void
    {
        [, $request] = $this->requestWithAcceptedQuote();
        $intruder = User::factory()->customer()->create();

        $this->actingAs($intruder)
            ->post(route('customer.payments.pay', $request))
            ->assertForbidden();

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_duplicate_payment_does_not_create_a_second_charge(): void
    {
        [$customer, $request] = $this->requestWithAcceptedQuote(500);

        $this->actingAs($customer)->post(route('customer.payments.pay', $request))->assertRedirect();

        $this->from(route('customer.requests.show', $request))
            ->actingAs($customer)
            ->post(route('customer.payments.pay', $request))
            ->assertSessionHasErrors();

        $this->assertDatabaseCount('payments', 1);
        $this->assertEqualsWithDelta(500.0, (float) Payment::first()->amount, 0.001);
    }
}
