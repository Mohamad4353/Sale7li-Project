<?php

namespace Tests\Feature\Payments;

use App\Models\Invoice;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * P10-17 — the invoice can be printed / saved as PDF from the browser. No PDF
 * dependency; a print stylesheet strips the app chrome. Financial values are
 * untouched.
 */
class InvoicePrintTest extends TestCase
{
    use BuildsPaidJobs, RefreshDatabase;

    /** @return array{customer: User, request: ServiceRequest} */
    private function paidRequest(): array
    {
        ['customer' => $customer, 'request' => $request] = $this->quotedCompletedJob(['base_price' => 140]);
        $this->actingAs($customer)->post(route('customer.payments.pay', $request))->assertRedirect();

        return ['customer' => $customer, 'request' => $request->fresh()];
    }

    public function test_the_invoice_page_offers_a_print_action(): void
    {
        ['customer' => $customer, 'request' => $request] = $this->paidRequest();

        $this->actingAs($customer)->get(route('customer.requests.invoice', $request))
            ->assertOk()
            ->assertSee(__('messages.print'))
            ->assertSee('window.print()', false)
            ->assertSee('print-sheet', false);
    }

    public function test_the_print_action_is_gated_by_invoice_ownership(): void
    {
        ['request' => $request] = $this->paidRequest();

        $this->actingAs(User::factory()->customer()->create())
            ->get(route('customer.requests.invoice', $request))
            ->assertForbidden();

        $this->app['auth']->forgetGuards();
        $this->get(route('customer.requests.invoice', $request))->assertRedirect(route('login'));
    }

    public function test_printing_does_not_alter_any_financial_value(): void
    {
        ['customer' => $customer, 'request' => $request] = $this->paidRequest();
        $invoice = Invoice::where('service_request_id', $request->id)->firstOrFail();
        $before = $invoice->only(['subtotal', 'discount', 'tax', 'total', 'line_items']);

        $this->actingAs($customer)->get(route('customer.requests.invoice', $request))->assertOk();

        $this->assertSame($before, $invoice->fresh()->only(['subtotal', 'discount', 'tax', 'total', 'line_items']));
    }
}
