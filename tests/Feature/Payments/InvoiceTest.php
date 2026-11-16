<?php

namespace Tests\Feature\Payments;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\RequestWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use BuildsPaidJobs, RefreshDatabase;

    private function pay(array $components): Invoice
    {
        ['customer' => $customer, 'request' => $request] = $this->quotedCompletedJob($components);
        $this->actingAs($customer)->post(route('customer.payments.pay', $request))->assertRedirect();

        return Invoice::where('service_request_id', $request->id)->firstOrFail();
    }

    public function test_invoice_columns_mirror_the_accepted_quotation_breakdown(): void
    {
        $invoice = $this->pay([
            'base_price' => 100, 'additional_charges' => 20, 'discount' => 10, 'tax' => 5,
        ]);

        // subtotal = base + additional ; discount/tax carried through ; total = quote total
        $this->assertSame('120.00', $invoice->subtotal);
        $this->assertSame('10.00', $invoice->discount);
        $this->assertSame('5.00', $invoice->tax);
        $this->assertSame('115.00', $invoice->total);

        // subtotal - discount + tax == total
        $this->assertSame(
            (int) round(($invoice->subtotal - $invoice->discount + $invoice->tax) * 100),
            (int) round($invoice->total * 100),
        );
    }

    public function test_invoice_line_items_reflect_each_non_zero_component(): void
    {
        $invoice = $this->pay([
            'base_price' => 100, 'additional_charges' => 20, 'discount' => 10, 'tax' => 5,
        ]);

        $labels = array_column($invoice->line_items, 'label');
        $this->assertContains('Additional charges', $labels);
        $this->assertContains('Discount', $labels);
        $this->assertContains('Tax', $labels);

        // sum of the line items equals the total
        $sumCents = 0;
        foreach ($invoice->line_items as $item) {
            $sumCents += (int) round(((float) $item['amount']) * 100);
        }
        $this->assertSame((int) round($invoice->total * 100), $sumCents);
    }

    public function test_zero_value_components_are_omitted_from_line_items_but_totals_stay_correct(): void
    {
        $invoice = $this->pay(['base_price' => 80]); // no additional/discount/tax

        $labels = array_column($invoice->line_items, 'label');
        $this->assertNotContains('Additional charges', $labels);
        $this->assertNotContains('Discount', $labels);
        $this->assertNotContains('Tax', $labels);

        $this->assertSame('80.00', $invoice->subtotal);
        $this->assertSame('0.00', $invoice->discount);
        $this->assertSame('0.00', $invoice->tax);
        $this->assertSame('80.00', $invoice->total);
    }

    public function test_invoice_total_equals_quotation_total_payment_amount_and_final_total(): void
    {
        ['customer' => $customer, 'request' => $request, 'quotation' => $quotation] = $this->quotedCompletedJob([
            'base_price' => 100, 'additional_charges' => 20, 'discount' => 10, 'tax' => 5,
        ]);
        $this->actingAs($customer)->post(route('customer.payments.pay', $request))->assertRedirect();

        $payment = Payment::where('service_request_id', $request->id)->firstOrFail();
        $invoice = Invoice::where('service_request_id', $request->id)->firstOrFail();

        $this->assertSame('115.00', $quotation->fresh()->total);
        $this->assertSame('115.00', $request->fresh()->final_total);
        $this->assertSame('115.00', $payment->amount);
        $this->assertSame('115.00', $invoice->total);
    }

    public function test_invoice_is_created_during_a_successful_payment(): void
    {
        ['customer' => $customer, 'request' => $request] = $this->quotedCompletedJob(['base_price' => 100]);
        $this->assertDatabaseCount('invoices', 0);

        $this->actingAs($customer)->post(route('customer.payments.pay', $request))->assertRedirect();

        $this->assertDatabaseHas('invoices', ['service_request_id' => $request->id, 'payment_status' => 'paid']);
    }

    public function test_a_failed_capture_leaves_no_orphan_invoice(): void
    {
        ['customer' => $customer, 'request' => $request] = $this->quotedCompletedJob(['base_price' => 100]);

        Event::listen('eloquent.creating: '.Payment::class, fn () => throw new \RuntimeException('payment write blew up'));
        $this->withoutExceptionHandling();

        try {
            $this->actingAs($customer)->post(route('customer.payments.pay', $request));
            $this->fail('expected the forced payment failure to abort the capture');
        } catch (\RuntimeException) {
        }

        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertSame('pending', $request->fresh()->payment_status);
    }

    public function test_invoice_does_not_use_customer_submitted_financial_values(): void
    {
        ['customer' => $customer, 'request' => $request] = $this->quotedCompletedJob([
            'base_price' => 100, 'tax' => 5,
        ]);

        $this->actingAs($customer)->post(route('customer.payments.pay', $request), [
            'subtotal' => 1, 'discount' => 999, 'tax' => 999, 'total' => 1,
            'line_items' => [['label' => 'HACK', 'amount' => 1]],
        ])->assertRedirect();

        $invoice = Invoice::where('service_request_id', $request->id)->firstOrFail();
        $this->assertSame('105.00', $invoice->total);   // 100 base + 5 tax, from the quote
        $this->assertSame('100.00', $invoice->subtotal); // base + additional
        $this->assertSame('0.00', $invoice->discount);
        $this->assertSame('5.00', $invoice->tax);
        $this->assertNotContains('HACK', array_column($invoice->line_items, 'label'));
    }

    public function test_completion_without_a_quotation_produces_a_simple_invoice_only_if_paid(): void
    {
        // job completed with no accepted quote -> final_total stays null -> not payable
        $customer = User::factory()->customer()->create();
        $worker = User::factory()->worker()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('in_progress')->create([
            'worker_id' => $worker->id,
        ]);

        app(RequestWorkflow::class)->transition($request, 'completed');

        $this->assertNull($request->fresh()->final_total);

        $this->from(route('customer.requests.show', $request))
            ->actingAs($customer)
            ->post(route('customer.payments.pay', $request))
            ->assertSessionHasErrors('payment');

        $this->assertDatabaseCount('invoices', 0);
    }
}
