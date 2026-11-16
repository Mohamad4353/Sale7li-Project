<?php

namespace Tests\Feature\Payments;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * P10-11 — Arabic invoices must be fully localized at PRESENTATION time only.
 * The persisted invoice (totals, per-line amounts) is frozen at capture and
 * never changes: quotation.total == invoice.total == payment.amount == final_total.
 */
class InvoiceLocalizationTest extends TestCase
{
    use BuildsPaidJobs, RefreshDatabase;

    /** @return array{customer: User, request: ServiceRequest, invoice: Invoice} */
    private function paidRequest(): array
    {
        ['customer' => $customer, 'request' => $request] = $this->quotedCompletedJob([
            'base_price' => 100, 'additional_charges' => 20, 'discount' => 10, 'tax' => 5,
        ]);
        $this->actingAs($customer)->post(route('customer.payments.pay', $request))->assertRedirect();

        return [
            'customer' => $customer,
            'request' => $request->fresh(),
            'invoice' => Invoice::where('service_request_id', $request->id)->firstOrFail(),
        ];
    }

    public function test_arabic_invoice_localizes_the_service_and_line_item_labels(): void
    {
        ['customer' => $customer, 'request' => $request] = $this->paidRequest();

        $response = $this->actingAs($customer)->withSession(['locale' => 'ar'])
            ->get(route('customer.requests.invoice', $request))
            ->assertOk();

        $response->assertSee($request->category->name_ar);
        $response->assertSee('رسوم إضافية');   // Additional charges
        $response->assertSee('الخصم');          // Discount
        $response->assertSee('الضريبة');        // Tax
        $response->assertDontSee('Additional charges');
        $response->assertDontSee($request->category->name);
    }

    public function test_english_invoice_labels_are_unchanged(): void
    {
        ['customer' => $customer, 'request' => $request] = $this->paidRequest();

        $this->actingAs($customer)->withSession(['locale' => 'en'])
            ->get(route('customer.requests.invoice', $request))
            ->assertOk()
            ->assertSee('Additional charges')
            ->assertSee($request->category->name);
    }

    public function test_the_frozen_financial_invariant_holds_regardless_of_locale(): void
    {
        ['customer' => $customer, 'request' => $request, 'invoice' => $invoice] = $this->paidRequest();
        $payment = Payment::where('service_request_id', $request->id)->firstOrFail();
        $quotation = $request->quotations()->where('status', 'accepted')->firstOrFail();

        // The persisted invoice is byte-identical no matter what locale rendered it.
        $this->actingAs($customer)->withSession(['locale' => 'ar'])
            ->get(route('customer.requests.invoice', $request))->assertOk()->assertSee('$115.00');

        $fresh = $invoice->fresh();
        $this->assertSame('115.00', $fresh->total);
        $this->assertSame('120.00', $fresh->subtotal);
        $this->assertSame('10.00', $fresh->discount);
        $this->assertSame('5.00', $fresh->tax);
        $this->assertSame($quotation->total, $fresh->total);
        $this->assertSame($payment->amount, $fresh->total);
        $this->assertSame($request->final_total, $fresh->total);
    }

    public function test_legacy_invoice_without_line_item_keys_still_renders_its_stored_label(): void
    {
        $customer = User::factory()->customer()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('completed')->create();
        Invoice::create([
            'invoice_number' => 'INV-LEGACY1',
            'service_request_id' => $request->id,
            'customer_id' => $customer->id,
            'worker_id' => null,
            'line_items' => [['label' => 'Legacy service line', 'amount' => '90.00']],
            'subtotal' => '90.00',
            'discount' => '0.00',
            'tax' => '0.00',
            'total' => '90.00',
            'payment_status' => 'paid',
        ]);

        $this->actingAs($customer)->withSession(['locale' => 'ar'])
            ->get(route('customer.requests.invoice', $request))
            ->assertOk()
            ->assertSee('Legacy service line');
    }
}
