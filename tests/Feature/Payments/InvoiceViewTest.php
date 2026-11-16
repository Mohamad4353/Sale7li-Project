<?php

namespace Tests\Feature\Payments;

use App\Models\Invoice;
use App\Models\PlatformSetting;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceViewTest extends TestCase
{
    use BuildsPaidJobs, RefreshDatabase;

    /** @return array{customer: User, request: ServiceRequest, invoice: Invoice} */
    private function paidRequest(array $components = ['base_price' => 100, 'additional_charges' => 20, 'discount' => 10, 'tax' => 5]): array
    {
        ['customer' => $customer, 'request' => $request] = $this->quotedCompletedJob($components);
        $this->actingAs($customer)->post(route('customer.payments.pay', $request))->assertRedirect();

        return [
            'customer' => $customer,
            'request' => $request->fresh(),
            'invoice' => Invoice::where('service_request_id', $request->id)->firstOrFail(),
        ];
    }

    public function test_the_owning_customer_can_view_their_invoice(): void
    {
        ['customer' => $customer, 'request' => $request, 'invoice' => $invoice] = $this->paidRequest();

        $response = $this->actingAs($customer)->get(route('customer.requests.invoice', $request))->assertOk();

        $response->assertSee($invoice->invoice_number);
        $response->assertSee('$115.00');  // total
        $response->assertSee('$120.00');  // subtotal (base + additional)
        $response->assertSee('$10.00');   // discount
        $response->assertSee('$5.00');    // tax
        $response->assertSee('Additional charges'); // a line item label
    }

    public function test_a_different_customer_cannot_view_the_invoice(): void
    {
        ['request' => $request] = $this->paidRequest();
        $intruder = User::factory()->customer()->create();

        $this->actingAs($intruder)->get(route('customer.requests.invoice', $request))->assertForbidden();
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        ['request' => $request] = $this->paidRequest();

        $this->app['auth']->forgetGuards(); // drop the acting-as user from paidRequest()

        $this->get(route('customer.requests.invoice', $request))->assertRedirect(route('login'));
    }

    public function test_a_worker_cannot_use_the_customer_invoice_route(): void
    {
        ['request' => $request, 'invoice' => $invoice] = $this->paidRequest();
        $assignedWorker = $request->worker;

        // role:customer middleware blocks the assigned worker from this route
        $this->actingAs($assignedWorker)->get(route('customer.requests.invoice', $request))->assertForbidden();
    }

    public function test_a_request_without_an_invoice_returns_404(): void
    {
        $customer = User::factory()->customer()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('accepted')->create();

        $this->actingAs($customer)->get(route('customer.requests.invoice', $request))->assertNotFound();
    }

    public function test_invoice_values_come_from_the_persisted_record_and_survive_a_rate_change(): void
    {
        ['customer' => $customer, 'request' => $request, 'invoice' => $invoice] = $this->paidRequest();

        PlatformSetting::setValue(PlatformSetting::COMMISSION_RATE, '0.5000');

        $response = $this->actingAs($customer)->get(route('customer.requests.invoice', $request))->assertOk();

        // unchanged persisted figures
        $response->assertSee('$115.00');
        $this->assertSame('115.00', $invoice->fresh()->total);
        $this->assertSame('120.00', $invoice->fresh()->subtotal);
    }

    public function test_line_items_render_from_the_stored_json(): void
    {
        ['customer' => $customer, 'request' => $request, 'invoice' => $invoice] = $this->paidRequest([
            'base_price' => 200, // no additional / discount / tax
        ]);

        $this->assertCount(1, $invoice->line_items);

        $this->actingAs($customer)->get(route('customer.requests.invoice', $request))
            ->assertOk()
            ->assertSee($invoice->line_items[0]['label'])
            ->assertSee('$200.00')
            ->assertDontSee('Additional charges');
    }
}
