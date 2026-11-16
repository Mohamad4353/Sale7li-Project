<?php

namespace Tests\Feature\Payments;

use App\Models\Payment;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * P10-24 — a successful payment lands on a receipt page that reads the persisted
 * payment record. No amount is recalculated; the page is authorization-gated.
 */
class PaymentReceiptTest extends TestCase
{
    use BuildsPaidJobs, RefreshDatabase;

    /** @return array{customer: User, request: ServiceRequest} */
    private function paid(): array
    {
        ['customer' => $customer, 'request' => $request] = $this->quotedCompletedJob(['base_price' => 120, 'tax' => 8]);
        $this->actingAs($customer)->post(route('customer.payments.pay', $request))
            ->assertRedirect(route('customer.requests.receipt', $request));

        return ['customer' => $customer, 'request' => $request->fresh()];
    }

    public function test_successful_payment_redirects_to_the_receipt(): void
    {
        ['customer' => $customer, 'request' => $request] = $this->paid();

        $this->actingAs($customer)->get(route('customer.requests.receipt', $request))
            ->assertOk()
            ->assertSee($request->reference)
            ->assertSee('$128.00')
            ->assertSee(route('customer.requests.invoice', $request), false);
    }

    public function test_receipt_shows_the_persisted_payment_amount_verbatim(): void
    {
        ['customer' => $customer, 'request' => $request] = $this->paid();
        $payment = Payment::where('service_request_id', $request->id)->firstOrFail();

        // Tamper with the request-level totals; the receipt must still read the payment row.
        $this->assertSame('128.00', $payment->amount);

        $this->actingAs($customer)->get(route('customer.requests.receipt', $request))
            ->assertOk()
            ->assertSee($payment->transaction_reference);
    }

    public function test_receipt_is_gated_by_ownership(): void
    {
        ['request' => $request] = $this->paid();

        $this->actingAs(User::factory()->customer()->create())
            ->get(route('customer.requests.receipt', $request))
            ->assertForbidden();

        $this->app['auth']->forgetGuards();
        $this->get(route('customer.requests.receipt', $request))->assertRedirect(route('login'));
    }

    public function test_receipt_404s_when_the_request_has_no_captured_payment(): void
    {
        $customer = User::factory()->customer()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('accepted')->create();

        $this->actingAs($customer)->get(route('customer.requests.receipt', $request))->assertNotFound();
    }

    public function test_receipt_is_localized(): void
    {
        ['customer' => $customer, 'request' => $request] = $this->paid();

        $this->actingAs($customer)->withSession(['locale' => 'ar'])
            ->get(route('customer.requests.receipt', $request))
            ->assertOk()
            ->assertSee(__('payments.receipt_title', [], 'ar'))
            ->assertDontSee(__('payments.receipt_title', [], 'en'));
    }
}
