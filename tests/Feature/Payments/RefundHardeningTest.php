<?php

namespace Tests\Feature\Payments;

use App\Models\ActivityLog;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use App\Payments\RefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

class RefundHardeningTest extends TestCase
{
    use BuildsPaidJobs, RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function capturedPayment(array $components = ['base_price' => 100]): Payment
    {
        ['customer' => $customer, 'request' => $request] = $this->quotedCompletedJob($components);
        $this->actingAs($customer)->post(route('customer.payments.pay', $request))->assertRedirect();

        return Payment::where('service_request_id', $request->id)->firstOrFail();
    }

    public function test_the_refund_and_its_audit_entry_are_written_atomically(): void
    {
        $payment = $this->capturedPayment();

        Event::listen('eloquent.creating: '.ActivityLog::class, fn () => throw new \RuntimeException('audit write blew up'));
        $this->withoutExceptionHandling();

        try {
            $this->actingAs($this->admin())->post(route('admin.payments.refund', $payment));
            $this->fail('a failing audit write must abort the refund');
        } catch (\RuntimeException) {
            // expected
        }

        $this->assertDatabaseCount('refunds', 0);
        $this->assertDatabaseMissing('activity_logs', ['action' => 'refund.completed']);
    }

    public function test_the_audit_entry_is_written_by_the_service_not_only_the_controller(): void
    {
        $payment = $this->capturedPayment();
        $admin = $this->admin();

        $refund = app(RefundService::class)->fullyRefund($payment, $admin, 'called directly');

        $log = ActivityLog::where('action', 'refund.completed')->firstOrFail();
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame($payment->id, $log->subject_id);
        $this->assertSame(Payment::class, $log->subject_type);
        $this->assertSame($refund->id, $log->metadata['refund_id']);
        $this->assertSame('100.00', $log->metadata['amount']);
    }

    public function test_a_pre_existing_incomplete_full_refund_row_yields_a_neutral_failure_not_a_partial_state(): void
    {
        $payment = $this->capturedPayment();
        Refund::factory()->pending()->ofPayment($payment)->create();

        $this->actingAs($this->admin())
            ->from(route('admin.payments.index'))
            ->post(route('admin.payments.refund', $payment))
            ->assertSessionHasErrors(['refund' => __('payments.refund_error_failed')]);

        $this->assertDatabaseCount('refunds', 1); // only the pending row
        $this->assertDatabaseMissing('refunds', ['status' => 'completed']);
        $this->assertDatabaseMissing('activity_logs', ['action' => 'refund.completed']);
    }

    public function test_the_refund_amount_and_ledger_fields_are_server_derived_and_request_input_is_ignored(): void
    {
        $payment = $this->capturedPayment(['base_price' => 100]);
        $intruder = User::factory()->worker()->create();

        $this->actingAs($this->admin())->post(route('admin.payments.refund', $payment), [
            'amount' => '1.00',
            'status' => 'pending',
            'type' => 'partial',
            'payment_id' => 999999,
            'worker_id' => $intruder->id,
            'processed_by' => $intruder->id,
            'commission_rate' => '0.0000',
            'commission_amount' => '0.00',
            'worker_net' => '999999.00',
        ])->assertRedirect();

        $refund = Refund::where('payment_id', $payment->id)->firstOrFail();
        $this->assertSame('100.00', $refund->amount);
        $this->assertSame('full', $refund->type);
        $this->assertSame('completed', $refund->status);
        $this->assertSame($payment->id, $refund->payment_id);
        $this->assertNotSame($intruder->id, $refund->processed_by);

        // the captured payment is untouched
        $payment->refresh();
        $this->assertSame('100.00', $payment->amount);
        $this->assertSame('0.1000', $payment->commission_rate);
        $this->assertSame('10.00', $payment->commission_amount);
        $this->assertSame('90.00', $payment->worker_net);
        $this->assertSame('paid', $payment->status);
    }

    public function test_a_failed_status_payment_cannot_be_refunded(): void
    {
        $payment = Payment::factory()->create(['status' => 'failed', 'worker_id' => User::factory()->worker()]);

        $this->actingAs($this->admin())
            ->from(route('admin.payments.index'))
            ->post(route('admin.payments.refund', $payment))
            ->assertSessionHasErrors(['refund' => __('payments.refund_error_not_paid')]);

        $this->assertDatabaseCount('refunds', 0);
    }

    public function test_a_reason_at_the_length_boundary_is_accepted_and_over_the_boundary_is_rejected(): void
    {
        $admin = $this->admin();

        $ok = $this->capturedPayment(['base_price' => 100]);
        $this->actingAs($admin)->post(route('admin.payments.refund', $ok), ['reason' => Str::repeat('a', 500)])->assertRedirect();
        $this->assertSame(500, strlen(Refund::where('payment_id', $ok->id)->value('reason')));

        $tooLong = $this->capturedPayment(['base_price' => 100]);
        $this->actingAs($admin)
            ->from(route('admin.payments.index'))
            ->post(route('admin.payments.refund', $tooLong), ['reason' => Str::repeat('a', 501)])
            ->assertSessionHasErrors('reason');
        $this->assertDatabaseMissing('refunds', ['payment_id' => $tooLong->id]);
    }

    public function test_an_empty_or_missing_reason_is_stored_as_null(): void
    {
        $admin = $this->admin();

        $a = $this->capturedPayment(['base_price' => 100]);
        $this->actingAs($admin)->post(route('admin.payments.refund', $a))->assertRedirect();
        $this->assertNull(Refund::where('payment_id', $a->id)->value('reason'));

        $b = $this->capturedPayment(['base_price' => 100]);
        $this->actingAs($admin)->post(route('admin.payments.refund', $b), ['reason' => ''])->assertRedirect();
        $this->assertNull(Refund::where('payment_id', $b->id)->value('reason'));
    }

    public function test_a_script_payload_in_the_reason_is_stored_verbatim_and_never_rendered_raw(): void
    {
        $payment = $this->capturedPayment(['base_price' => 100]);
        $payload = '<script>alert(1)</script>';

        $this->actingAs($this->admin())->post(route('admin.payments.refund', $payment), ['reason' => $payload])->assertRedirect();
        $this->assertSame($payload, Refund::where('payment_id', $payment->id)->value('reason'));

        // the reason is audit-only; it must not reach any page unescaped
        $this->actingAs($this->admin())->get(route('admin.payments.index'))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false);
    }
}
