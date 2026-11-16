<?php

namespace Tests\Feature\Payments;

use App\Models\Payment;
use App\Models\PlatformSetting;
use App\Models\Refund;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Payments\Reconciliation\ReconciliationReport;
use App\Payments\ReconciliationService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReconciliationTest extends TestCase
{
    use BuildsPaidJobs, RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    /** @return array{customer: User, request: ServiceRequest, payment: Payment} */
    private function capturedPayment(array $components = ['base_price' => 100]): array
    {
        ['customer' => $customer, 'request' => $request] = $this->quotedCompletedJob($components);
        $this->actingAs($customer)->post(route('customer.payments.pay', $request))->assertRedirect();

        return [
            'customer' => $customer,
            'request' => $request->fresh(),
            'payment' => Payment::where('service_request_id', $request->id)->firstOrFail(),
        ];
    }

    private function report(): ReconciliationReport
    {
        return app(ReconciliationService::class)->report();
    }

    /** @return string[] */
    private function codes(): array
    {
        return array_map(fn ($d) => $d->code, $this->report()->discrepancies);
    }

    public function test_a_clean_ledger_reports_no_discrepancies(): void
    {
        $this->capturedPayment(['base_price' => 100]);

        $report = $this->report();
        $this->assertTrue($report->isClean());
        $this->assertSame(1, $report->checked);
        $this->assertSame(1, $report->reconciled);
    }

    public function test_a_fully_refunded_payment_still_reconciles(): void
    {
        ['payment' => $payment] = $this->capturedPayment(['base_price' => 100]);
        Refund::factory()->ofPayment($payment)->create();

        $report = $this->report();
        $this->assertTrue($report->isClean());
        $this->assertSame(1, $report->refundedPayments);
        $this->assertSame('100.00', $report->totalRefunded);
        $this->assertSame('0.00', $report->retained);
    }

    public function test_an_invoice_total_mismatch_is_flagged(): void
    {
        ['request' => $request] = $this->capturedPayment(['base_price' => 100]);
        DB::table('invoices')->where('service_request_id', $request->id)->update(['total' => '999.00']);

        $this->assertContains('invoice_total_mismatch', $this->codes());
    }

    public function test_a_final_total_mismatch_is_flagged(): void
    {
        ['request' => $request] = $this->capturedPayment(['base_price' => 100]);
        DB::table('service_requests')->where('id', $request->id)->update(['final_total' => '777.00']);

        $this->assertContains('final_total_mismatch', $this->codes());
    }

    public function test_a_paid_payment_with_no_invoice_is_flagged(): void
    {
        Payment::factory()->paid()->create(['worker_id' => User::factory()->worker()]);

        $this->assertContains('missing_invoice', $this->codes());
    }

    public function test_an_over_refund_is_flagged(): void
    {
        ['payment' => $payment] = $this->capturedPayment(['base_price' => 100]);
        Refund::factory()->create(['payment_id' => $payment->id, 'amount' => '999.00']);

        $this->assertContains('over_refund', $this->codes());
    }

    public function test_a_negative_refund_is_flagged(): void
    {
        ['payment' => $payment] = $this->capturedPayment(['base_price' => 100]);
        DB::table('refunds')->insert([
            'payment_id' => $payment->id, 'amount' => '-5.00', 'type' => 'full', 'status' => 'failed',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->assertContains('negative_refund', $this->codes());
    }

    public function test_a_negative_payment_amount_is_flagged(): void
    {
        Payment::factory()->paid()->create(['amount' => '-1.00', 'worker_id' => User::factory()->worker()]);

        $this->assertContains('negative_amount', $this->codes());
    }

    public function test_duplicate_full_refunds_are_prevented_by_the_database(): void
    {
        ['payment' => $payment] = $this->capturedPayment(['base_price' => 100]);
        Refund::factory()->ofPayment($payment)->create();

        $this->expectException(QueryException::class);
        Refund::factory()->ofPayment($payment)->create();
    }

    public function test_reconciliation_never_mutates_a_record(): void
    {
        ['payment' => $payment, 'request' => $request] = $this->capturedPayment(['base_price' => 100]);
        Refund::factory()->ofPayment($payment)->create();

        $paymentBefore = $payment->fresh()->only(['amount', 'commission_rate', 'commission_amount', 'worker_net', 'status']);
        $invoiceBefore = DB::table('invoices')->where('service_request_id', $request->id)->first();
        $refundBefore = DB::table('refunds')->where('payment_id', $payment->id)->first();

        $this->report();

        $this->assertSame($paymentBefore, $payment->fresh()->only(array_keys($paymentBefore)));
        $this->assertEquals($invoiceBefore, DB::table('invoices')->where('service_request_id', $request->id)->first());
        $this->assertEquals($refundBefore, DB::table('refunds')->where('payment_id', $payment->id)->first());
    }

    public function test_reconciliation_requires_an_admin(): void
    {
        $this->get(route('admin.payments.reconciliation'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->customer()->create())->get(route('admin.payments.reconciliation'))->assertForbidden();
        $this->actingAs(User::factory()->worker()->create())->get(route('admin.payments.reconciliation'))->assertForbidden();
        $this->actingAs($this->admin())->get(route('admin.payments.reconciliation'))->assertOk();
    }

    public function test_the_page_renders_a_clean_state_and_a_discrepancy_state(): void
    {
        ['request' => $request] = $this->capturedPayment(['base_price' => 100]);

        $this->actingAs($this->admin())->get(route('admin.payments.reconciliation'))
            ->assertOk()
            ->assertSee(__('payments.recon_clean_title'));

        DB::table('invoices')->where('service_request_id', $request->id)->update(['total' => '999.00']);

        $this->actingAs($this->admin())->get(route('admin.payments.reconciliation'))
            ->assertOk()
            ->assertSee(__('payments.recon_code_invoice_total_mismatch'));
    }

    public function test_reconciliation_does_not_recompute_from_the_live_commission_rate(): void
    {
        ['payment' => $payment] = $this->capturedPayment(['base_price' => 100]);

        PlatformSetting::setValue(PlatformSetting::COMMISSION_RATE, '0.2500');

        $report = $this->report();
        $this->assertTrue($report->isClean());
        $this->assertSame('100.00', $report->grossCaptured);

        $payment->refresh();
        $this->assertSame('0.1000', $payment->commission_rate);
        $this->assertSame('10.00', $payment->commission_amount);
    }

    public function test_summary_totals_use_persisted_amounts(): void
    {
        ['payment' => $p1] = $this->capturedPayment(['base_price' => 100]);
        $this->capturedPayment(['base_price' => 300]);
        Refund::factory()->ofPayment($p1)->create();

        $report = $this->report();
        $this->assertSame(2, $report->checked);
        $this->assertSame(2, $report->reconciled);
        $this->assertSame(1, $report->refundedPayments);
        $this->assertSame('400.00', $report->grossCaptured);
        $this->assertSame('100.00', $report->totalRefunded);
        $this->assertSame('300.00', $report->retained);
    }

    public function test_reconciliation_runs_a_bounded_number_of_queries(): void
    {
        for ($i = 0; $i < 6; $i++) {
            ['payment' => $payment] = $this->capturedPayment(['base_price' => 100 + $i]);
            if ($i % 2 === 0) {
                Refund::factory()->ofPayment($payment)->create();
            }
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->report();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(25, $count, 'reconciliation should run a bounded number of queries');
    }
}
