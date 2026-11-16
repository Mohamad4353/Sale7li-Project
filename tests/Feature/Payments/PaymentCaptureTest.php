<?php

namespace Tests\Feature\Payments;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PlatformSetting;
use App\Models\Quotation;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class PaymentCaptureTest extends TestCase
{
    use BuildsPaidJobs, RefreshDatabase;

    public function test_successful_capture_freezes_worker_rate_commission_and_net(): void
    {
        // base 100 + additional 20 - discount 10 + tax 5 = 115.00
        ['customer' => $customer, 'worker' => $worker, 'request' => $request] = $this->quotedCompletedJob([
            'base_price' => 100, 'additional_charges' => 20, 'discount' => 10, 'tax' => 5,
        ]);

        $this->actingAs($customer)->post(route('customer.payments.pay', $request))->assertRedirect();

        $payment = Payment::where('service_request_id', $request->id)->firstOrFail();
        $this->assertSame($worker->id, $payment->worker_id);
        $this->assertSame('115.00', $payment->amount);
        $this->assertSame('0.1000', $payment->commission_rate);
        $this->assertSame('11.50', $payment->commission_amount);
        $this->assertSame('103.50', $payment->worker_net);
        $this->assertSame('paid', $payment->status);
        $this->assertSame('USD', $payment->currency);
        $this->assertNotNull($payment->paid_at);
    }

    public function test_amount_is_server_derived_and_request_input_is_ignored(): void
    {
        ['customer' => $customer, 'request' => $request, 'worker' => $worker] = $this->quotedCompletedJob(['base_price' => 200]);
        $intruder = User::factory()->worker()->create();

        $this->actingAs($customer)->post(route('customer.payments.pay', $request), [
            'amount' => 1,
            'total' => 1,
            'quoted_total' => 1,
            'final_total' => 1,
            'currency' => 'XXX',
            'worker_id' => $intruder->id,
            'commission_rate' => '0.0000',
            'commission_amount' => 0,
            'worker_net' => 999999,
            'payment_status' => 'refunded',
        ])->assertRedirect();

        $payment = Payment::where('service_request_id', $request->id)->firstOrFail();
        $this->assertSame('200.00', $payment->amount);
        $this->assertSame($worker->id, $payment->worker_id);
        $this->assertNotSame($intruder->id, $payment->worker_id);
        $this->assertSame('0.1000', $payment->commission_rate);
        $this->assertSame('20.00', $payment->commission_amount);
        $this->assertSame('180.00', $payment->worker_net);
        $this->assertSame('USD', $payment->currency);
        $this->assertSame('paid', $payment->status);
    }

    public function test_commission_rate_is_frozen_and_a_later_platform_change_does_not_touch_old_payments(): void
    {
        ['customer' => $c1, 'request' => $r1] = $this->quotedCompletedJob(['base_price' => 100]);
        $this->actingAs($c1)->post(route('customer.payments.pay', $r1))->assertRedirect();

        $first = Payment::where('service_request_id', $r1->id)->firstOrFail();
        $this->assertSame('0.1000', $first->commission_rate);
        $this->assertSame('10.00', $first->commission_amount);
        $this->assertSame('90.00', $first->worker_net);

        PlatformSetting::setValue(PlatformSetting::COMMISSION_RATE, '0.1500');

        // untouched
        $first->refresh();
        $this->assertSame('0.1000', $first->commission_rate);
        $this->assertSame('10.00', $first->commission_amount);
        $this->assertSame('90.00', $first->worker_net);

        // a new payment uses the new active rate
        ['customer' => $c2, 'request' => $r2] = $this->quotedCompletedJob(['base_price' => 100]);
        $this->actingAs($c2)->post(route('customer.payments.pay', $r2))->assertRedirect();

        $second = Payment::where('service_request_id', $r2->id)->firstOrFail();
        $this->assertSame('0.1500', $second->commission_rate);
        $this->assertSame('15.00', $second->commission_amount);
        $this->assertSame('85.00', $second->worker_net);
    }

    public function test_payment_amount_equals_invoice_total_and_final_total(): void
    {
        ['customer' => $customer, 'request' => $request] = $this->quotedCompletedJob([
            'base_price' => 100, 'additional_charges' => 20, 'discount' => 10, 'tax' => 5,
        ]);

        $this->assertSame('115.00', $request->final_total);

        $this->actingAs($customer)->post(route('customer.payments.pay', $request))->assertRedirect();

        $payment = Payment::where('service_request_id', $request->id)->firstOrFail();
        $invoice = Invoice::where('service_request_id', $request->id)->firstOrFail();

        $this->assertSame('115.00', $payment->amount);
        $this->assertSame('115.00', $invoice->total);
        $this->assertSame('115.00', $request->fresh()->final_total);
        $this->assertSame($payment->amount, $invoice->total);
        $this->assertSame($payment->amount, $request->fresh()->final_total);
    }

    public function test_no_payable_amount_creates_nothing_and_does_not_mark_paid(): void
    {
        $customer = User::factory()->customer()->create();
        $worker = User::factory()->worker()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('accepted')->create([
            'worker_id' => $worker->id,
        ]);

        $this->from(route('customer.requests.show', $request))
            ->actingAs($customer)
            ->post(route('customer.payments.pay', $request))
            ->assertSessionHasErrors('payment');

        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('invoices', 0);
        $this->assertSame('pending', $request->fresh()->payment_status);
    }

    public function test_payment_is_rejected_when_the_request_has_no_assigned_worker(): void
    {
        $customer = User::factory()->customer()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('accepted')->create([
            'worker_id' => null,
            'quoted_total' => 100,
        ]);
        // simulate an accepted quote existing without a worker (defensive path)
        Quotation::factory()->create([
            'service_request_id' => $request->id,
            'status' => 'accepted',
            'total' => 100,
        ]);

        $this->from(route('customer.requests.show', $request))
            ->actingAs($customer)
            ->post(route('customer.payments.pay', $request))
            ->assertSessionHasErrors('payment');

        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('invoices', 0);
        $this->assertSame('pending', $request->fresh()->payment_status);
    }

    public function test_duplicate_payment_is_rejected_and_only_one_row_exists(): void
    {
        ['customer' => $customer, 'request' => $request] = $this->quotedCompletedJob(['base_price' => 100]);

        $this->actingAs($customer)->post(route('customer.payments.pay', $request))->assertRedirect();

        $this->from(route('customer.requests.show', $request))
            ->actingAs($customer)
            ->post(route('customer.payments.pay', $request))
            ->assertSessionHasErrors('payment');

        $this->assertDatabaseCount('payments', 1);
        $this->assertSame('100.00', Payment::first()->amount);
    }

    public function test_the_database_rejects_a_second_payment_row_for_one_request(): void
    {
        ['request' => $request, 'worker' => $worker] = $this->quotedCompletedJob(['base_price' => 100]);
        Payment::factory()->create(['service_request_id' => $request->id]);

        $this->expectException(QueryException::class);
        Payment::factory()->create(['service_request_id' => $request->id]);
    }

    public function test_capture_is_atomic_a_failing_invoice_write_rolls_everything_back(): void
    {
        ['customer' => $customer, 'request' => $request] = $this->quotedCompletedJob(['base_price' => 100]);

        // Force the invoice insert to fail *after* the payment insert + status update.
        Event::listen('eloquent.creating: '.Invoice::class, fn () => throw new \RuntimeException('invoice write blew up'));

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($customer)->post(route('customer.payments.pay', $request));
            $this->fail('expected the forced invoice failure to abort the capture');
        } catch (\RuntimeException $e) {
            // expected — the controller does not catch it, so nothing commits
        }

        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('invoices', 0);
        $this->assertSame('pending', $request->fresh()->payment_status);
    }

    public function test_existing_authorization_still_blocks_a_foreign_customer(): void
    {
        ['request' => $request] = $this->quotedCompletedJob(['base_price' => 100]);
        $intruder = User::factory()->customer()->create();

        $this->actingAs($intruder)->post(route('customer.payments.pay', $request))->assertForbidden();
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_worker_net_equals_amount_minus_commission_for_odd_cents(): void
    {
        ['customer' => $customer, 'request' => $request] = $this->quotedCompletedJob(['base_price' => '99.99']);
        $this->actingAs($customer)->post(route('customer.payments.pay', $request))->assertRedirect();

        $payment = Payment::where('service_request_id', $request->id)->firstOrFail();
        $this->assertSame('99.99', $payment->amount);
        $this->assertSame('10.00', $payment->commission_amount); // 999.9c -> half up -> 1000c
        $this->assertSame('89.99', $payment->worker_net);
        $this->assertSame(
            (int) round(($payment->amount - $payment->commission_amount) * 100),
            (int) round($payment->worker_net * 100),
        );
    }

    public function test_capture_runs_a_bounded_number_of_queries(): void
    {
        ['customer' => $customer, 'request' => $request] = $this->quotedCompletedJob(['base_price' => 100]);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($customer)->post(route('customer.payments.pay', $request))->assertRedirect();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(20, $count, 'payment capture should not run an unbounded number of queries');
    }
}
