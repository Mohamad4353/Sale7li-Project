<?php

namespace Tests\Feature\Payments;

use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use App\Payments\ReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReconciliationHardeningTest extends TestCase
{
    use BuildsPaidJobs, RefreshDatabase;

    private function capturedPayment(array $components = ['base_price' => 100]): array
    {
        ['customer' => $customer, 'request' => $request] = $this->quotedCompletedJob($components);
        $this->actingAs($customer)->post(route('customer.payments.pay', $request))->assertRedirect();

        return [
            'request' => $request->fresh(),
            'payment' => Payment::where('service_request_id', $request->id)->firstOrFail(),
        ];
    }

    /** @return string[] */
    private function codes(): array
    {
        return array_map(fn ($d) => $d->code, app(ReconciliationService::class)->report()->discrepancies);
    }

    public function test_multiple_discrepancies_on_one_payment_are_all_reported_but_counted_once(): void
    {
        ['request' => $request] = $this->capturedPayment(['base_price' => 100]);
        DB::table('invoices')->where('service_request_id', $request->id)->update(['total' => '999.00']);
        DB::table('service_requests')->where('id', $request->id)->update(['final_total' => '888.00']);

        $report = app(ReconciliationService::class)->report();
        $codes = array_map(fn ($d) => $d->code, $report->discrepancies);

        $this->assertContains('invoice_total_mismatch', $codes);
        $this->assertContains('final_total_mismatch', $codes);
        $this->assertGreaterThanOrEqual(2, $report->discrepancyCount());
        $this->assertSame(1, $report->checked);
        $this->assertSame(0, $report->reconciled); // the one payment is flagged, not double-subtracted
    }

    public function test_a_negative_payment_that_also_has_a_refund_does_not_crash_and_flags_both(): void
    {
        $payment = Payment::factory()->paid()->create(['amount' => '-1.00', 'worker_id' => User::factory()->worker()]);
        Refund::factory()->create(['payment_id' => $payment->id, 'amount' => '50.00']);

        $codes = $this->codes(); // must not throw

        $this->assertContains('negative_amount', $codes);
        $this->assertContains('over_refund', $codes);
    }

    public function test_one_corrupted_row_does_not_poison_the_headline_totals(): void
    {
        ['payment' => $p1] = $this->capturedPayment(['base_price' => 100]);
        ['payment' => $p2] = $this->capturedPayment(['base_price' => 300]);
        Refund::factory()->ofPayment($p1)->create(); // legit full refund of 100

        // corrupt rows on other payments
        Payment::factory()->paid()->create(['amount' => '-5.00', 'worker_id' => User::factory()->worker()]);
        DB::table('refunds')->insert([
            'payment_id' => $p2->id, 'amount' => '-9.00', 'type' => 'full', 'status' => 'failed',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $report = app(ReconciliationService::class)->report();

        $this->assertSame('400.00', $report->grossCaptured);  // -5 excluded
        $this->assertSame('100.00', $report->totalRefunded);   // -9 excluded
        $this->assertSame('300.00', $report->retained);

        $codes = array_map(fn ($d) => $d->code, $report->discrepancies);
        $this->assertContains('negative_amount', $codes);
        $this->assertContains('negative_refund', $codes);
    }

    public function test_a_null_final_total_is_not_treated_as_a_mismatch(): void
    {
        // quotedAcceptedJob leaves final_total null; payment uses quoted_total.
        ['customer' => $customer, 'request' => $request] = $this->quotedAcceptedJob(['base_price' => 100]);
        $this->actingAs($customer)->post(route('customer.payments.pay', $request))->assertRedirect();

        $this->assertNull($request->fresh()->final_total);

        $report = app(ReconciliationService::class)->report();
        $this->assertTrue($report->isClean());
    }

    public function test_a_corrupt_non_paid_payment_is_flagged_but_does_not_dent_reconciled(): void
    {
        ['payment' => $clean] = $this->capturedPayment(['base_price' => 100]);

        // a non-paid row with a garbage amount, and an over-refund on another non-paid row
        Payment::factory()->create(['status' => 'pending', 'amount' => '-1.00', 'worker_id' => User::factory()->worker()]);
        $failed = Payment::factory()->create(['status' => 'failed', 'amount' => '50.00', 'worker_id' => User::factory()->worker()]);
        Refund::factory()->create(['payment_id' => $failed->id, 'amount' => '999.00']);

        $report = app(ReconciliationService::class)->report();
        $codes = array_map(fn ($d) => $d->code, $report->discrepancies);

        $this->assertSame(1, $report->checked, 'only the one paid payment is checked');
        $this->assertSame(1, $report->reconciled, 'the paid payment reconciles; non-paid corruption is separate');
        $this->assertContains('negative_amount', $codes);
        $this->assertContains('over_refund', $codes);
        $this->assertNotContains($clean->id, array_map(fn ($d) => $d->paymentId, $report->discrepancies), 'the clean paid payment is never a finding');
    }

    public function test_every_discrepancy_code_has_a_localized_label_in_both_locales(): void
    {
        $codes = [
            'missing_invoice', 'invoice_total_mismatch', 'final_total_mismatch', 'over_refund',
            'full_refund_amount_mismatch', 'duplicate_refund', 'negative_amount', 'negative_refund', 'orphan_refund',
        ];

        foreach ($codes as $code) {
            foreach (['en', 'ar'] as $locale) {
                $label = __('payments.recon_code_'.$code, [], $locale);
                $this->assertStringNotContainsString('payments.recon_code_', $label, "$code missing in $locale");
                $this->assertNotSame('', trim($label));
            }
        }
    }
}
