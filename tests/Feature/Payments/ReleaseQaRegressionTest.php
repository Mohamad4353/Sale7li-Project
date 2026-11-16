<?php

namespace Tests\Feature\Payments;

use App\Models\Payment;
use App\Models\User;
use App\Payments\ReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression cover for multi-step scenarios verified during the Phase 8 release
 * QA that had no single existing test: the cancel-after-pay → admin refund →
 * reconciliation path, and money-boundary capture end to end.
 */
class ReleaseQaRegressionTest extends TestCase
{
    use BuildsPaidJobs, RefreshDatabase;

    private function scheduleAndPay(array $components): array
    {
        ['customer' => $customer, 'worker' => $worker, 'request' => $request] = $this->quotedAcceptedJob($components);

        $this->actingAs($worker)->post(route('worker.jobs.status', $request), [
            'status' => 'scheduled',
            'scheduled_date' => now()->addDay()->toDateString(),
            'scheduled_time' => '10:00',
        ])->assertRedirect();

        $this->actingAs($customer)->post(route('customer.payments.pay', $request))->assertRedirect();

        return [
            'customer' => $customer,
            'request' => $request->fresh(),
            'payment' => Payment::where('service_request_id', $request->id)->firstOrFail(),
        ];
    }

    public function test_cancel_after_pay_then_admin_refund_stays_financially_consistent(): void
    {
        ['customer' => $customer, 'request' => $request, 'payment' => $payment] = $this->scheduleAndPay(['base_price' => 100]);

        // The documented cancel-after-pay gap: a paid, scheduled job can be cancelled.
        $this->actingAs($customer)->post(route('customer.requests.cancel', $request))->assertRedirect();
        $this->assertSame('cancelled', $request->fresh()->status);
        $this->assertSame('paid', $request->fresh()->payment_status);

        // A paid + cancelled job with no refund yet is still financially consistent.
        $this->assertTrue(app(ReconciliationService::class)->report()->isClean());

        // Re-payment is impossible.
        $this->actingAs($customer)
            ->from(route('customer.requests.show', $request))
            ->post(route('customer.payments.pay', $request))
            ->assertSessionHasErrors('payment');
        $this->assertDatabaseCount('payments', 1);

        // The admin can refund it; the payment history stays frozen.
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.payments.refund', $payment))->assertRedirect();

        $payment->refresh();
        $this->assertSame('100.00', $payment->amount);
        $this->assertSame('0.1000', $payment->commission_rate);
        $this->assertSame('10.00', $payment->commission_amount);
        $this->assertSame('90.00', $payment->worker_net);
        $this->assertSame('paid', $payment->status);

        $report = app(ReconciliationService::class)->report();
        $this->assertTrue($report->isClean());
        $this->assertSame(1, $report->refundedPayments);
        $this->assertSame('0.00', $report->retained);
    }

    public function test_money_boundary_amounts_capture_with_half_up_rounding_and_reconcile(): void
    {
        // $0.01 @ 10% = 0.1c -> rounds down -> $0.00 commission, $0.01 net
        ['customer' => $c1, 'request' => $r1] = $this->quotedCompletedJob(['base_price' => '0.01']);
        $this->actingAs($c1)->post(route('customer.payments.pay', $r1))->assertRedirect();
        $p1 = Payment::where('service_request_id', $r1->id)->firstOrFail();
        $this->assertSame(['0.01', '0.00', '0.01'], [$p1->amount, $p1->commission_amount, $p1->worker_net]);

        // $999,999.99 @ 10% = 9,999,999.9c -> half up -> $100,000.00 commission
        ['customer' => $c2, 'request' => $r2] = $this->quotedCompletedJob(['base_price' => '999999.99']);
        $this->actingAs($c2)->post(route('customer.payments.pay', $r2))->assertRedirect();
        $p2 = Payment::where('service_request_id', $r2->id)->firstOrFail();
        $this->assertSame(['999999.99', '100000.00', '899999.99'], [$p2->amount, $p2->commission_amount, $p2->worker_net]);

        $this->assertTrue(app(ReconciliationService::class)->report()->isClean());
    }
}
