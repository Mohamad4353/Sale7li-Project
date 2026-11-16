<?php

namespace Tests\Feature\Payments;

use App\Models\ActivityLog;
use App\Models\Payment;
use App\Models\PlatformSetting;
use App\Models\Refund;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Payments\RefundException;
use App\Payments\RefundService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RefundTest extends TestCase
{
    use BuildsPaidJobs, RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    /** @return array{customer: User, worker: User, request: ServiceRequest, payment: Payment} */
    private function capturedPayment(array $components = ['base_price' => 100]): array
    {
        ['customer' => $customer, 'worker' => $worker, 'request' => $request] = $this->quotedCompletedJob($components);
        $this->actingAs($customer)->post(route('customer.payments.pay', $request))->assertRedirect();

        return [
            'customer' => $customer,
            'worker' => $worker,
            'request' => $request->fresh(),
            'payment' => Payment::where('service_request_id', $request->id)->firstOrFail(),
        ];
    }

    public function test_an_admin_can_fully_refund_a_paid_payment(): void
    {
        ['payment' => $payment] = $this->capturedPayment(['base_price' => 100]);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.payments.refund', $payment))
            ->assertRedirect()
            ->assertSessionHas('success');

        $refund = Refund::where('payment_id', $payment->id)->firstOrFail();
        $this->assertSame('100.00', $refund->amount);
        $this->assertSame('full', $refund->type);
        $this->assertSame('completed', $refund->status);
        $this->assertSame($admin->id, $refund->processed_by);
        $this->assertNotNull($refund->processed_at);
    }

    public function test_the_refund_amount_equals_the_captured_payment_amount(): void
    {
        // 100 + 20 - 10 + 5 = 115.00
        ['payment' => $payment] = $this->capturedPayment([
            'base_price' => 100, 'additional_charges' => 20, 'discount' => 10, 'tax' => 5,
        ]);

        $this->actingAs($this->admin())->post(route('admin.payments.refund', $payment))->assertRedirect();

        $this->assertSame('115.00', Refund::where('payment_id', $payment->id)->value('amount'));
    }

    public function test_a_refund_never_mutates_the_captured_payment(): void
    {
        ['payment' => $payment] = $this->capturedPayment(['base_price' => 100]);
        $before = $payment->only(['amount', 'commission_rate', 'commission_amount', 'worker_net', 'worker_id', 'status']);
        $paidAtBefore = $payment->paid_at->toDateTimeString();

        $this->actingAs($this->admin())->post(route('admin.payments.refund', $payment))->assertRedirect();

        $this->assertSame($before, $payment->fresh()->only(array_keys($before)));
        $this->assertSame($paidAtBefore, $payment->fresh()->paid_at->toDateTimeString());
        $this->assertSame('0.1000', $payment->fresh()->commission_rate);
        $this->assertSame('10.00', $payment->fresh()->commission_amount);
        $this->assertSame('90.00', $payment->fresh()->worker_net);
        $this->assertSame('paid', $payment->fresh()->status);
    }

    public function test_a_later_commission_rate_change_does_not_alter_the_refund_or_payment(): void
    {
        ['payment' => $payment] = $this->capturedPayment(['base_price' => 100]);
        $this->actingAs($this->admin())->post(route('admin.payments.refund', $payment))->assertRedirect();

        PlatformSetting::setValue(PlatformSetting::COMMISSION_RATE, '0.2500');

        $payment->refresh();
        $this->assertSame('0.1000', $payment->commission_rate);
        $this->assertSame('10.00', $payment->commission_amount);
        $this->assertSame('90.00', $payment->worker_net);
        $this->assertSame('100.00', Refund::where('payment_id', $payment->id)->value('amount'));
    }

    public function test_a_pending_payment_cannot_be_refunded(): void
    {
        $payment = Payment::factory()->create(['status' => 'pending', 'worker_id' => User::factory()->worker()]);

        $this->actingAs($this->admin())
            ->from(route('admin.payments.index'))
            ->post(route('admin.payments.refund', $payment))
            ->assertSessionHasErrors('refund');

        $this->assertDatabaseCount('refunds', 0);
    }

    public function test_a_nonexistent_payment_returns_404(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.payments.refund', 999999))
            ->assertNotFound();
    }

    public function test_a_payment_cannot_be_fully_refunded_twice(): void
    {
        ['payment' => $payment] = $this->capturedPayment(['base_price' => 100]);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.payments.refund', $payment))->assertRedirect();

        $this->actingAs($admin)
            ->from(route('admin.payments.index'))
            ->post(route('admin.payments.refund', $payment))
            ->assertSessionHasErrors('refund');

        $this->assertDatabaseCount('refunds', 1);
    }

    public function test_the_database_rejects_a_second_full_refund_row_for_one_payment(): void
    {
        ['payment' => $payment] = $this->capturedPayment(['base_price' => 100]);
        Refund::factory()->ofPayment($payment)->create();

        $this->expectException(QueryException::class);
        Refund::factory()->ofPayment($payment)->create();
    }

    public function test_repeated_service_calls_produce_exactly_one_refund(): void
    {
        ['payment' => $payment] = $this->capturedPayment(['base_price' => 100]);
        $service = app(RefundService::class);

        $service->fullyRefund($payment);

        try {
            $service->fullyRefund($payment->fresh());
            $this->fail('a second full refund should be rejected');
        } catch (RefundException $e) {
            $this->assertSame('payments.refund_error_already_refunded', $e->reasonKey);
        }

        $this->assertDatabaseCount('refunds', 1);
    }

    public function test_the_service_rejects_a_payment_that_is_not_paid(): void
    {
        $payment = Payment::factory()->create(['status' => 'pending', 'worker_id' => User::factory()->worker()]);

        $this->expectException(RefundException::class);
        app(RefundService::class)->fullyRefund($payment);
    }

    public function test_a_guest_cannot_refund(): void
    {
        ['payment' => $payment] = $this->capturedPayment(['base_price' => 100]);
        $this->app['auth']->forgetGuards();

        $this->post(route('admin.payments.refund', $payment))->assertRedirect(route('login'));
        $this->assertDatabaseCount('refunds', 0);
    }

    public function test_a_customer_cannot_refund(): void
    {
        ['customer' => $customer, 'payment' => $payment] = $this->capturedPayment(['base_price' => 100]);

        $this->actingAs($customer)->post(route('admin.payments.refund', $payment))->assertForbidden();
        $this->assertDatabaseCount('refunds', 0);
    }

    public function test_a_worker_cannot_refund(): void
    {
        ['worker' => $worker, 'payment' => $payment] = $this->capturedPayment(['base_price' => 100]);

        $this->actingAs($worker)->post(route('admin.payments.refund', $payment))->assertForbidden();
        $this->assertDatabaseCount('refunds', 0);
    }

    public function test_a_refund_writes_an_activity_log_entry(): void
    {
        ['payment' => $payment] = $this->capturedPayment(['base_price' => 100]);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.payments.refund', $payment))->assertRedirect();

        $refund = Refund::where('payment_id', $payment->id)->firstOrFail();
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'action' => 'refund.completed',
            'subject_type' => Payment::class,
            'subject_id' => $payment->id,
        ]);

        $log = ActivityLog::where('action', 'refund.completed')->firstOrFail();
        $this->assertSame($refund->id, $log->metadata['refund_id']);
        $this->assertSame($payment->id, $log->metadata['payment_id']);
    }

    public function test_a_failing_write_rolls_the_whole_refund_back(): void
    {
        ['payment' => $payment] = $this->capturedPayment(['base_price' => 100]);

        Event::listen('eloquent.creating: '.Refund::class, fn () => throw new \RuntimeException('refund write blew up'));
        $this->withoutExceptionHandling();

        try {
            $this->actingAs($this->admin())->post(route('admin.payments.refund', $payment));
            $this->fail('expected the forced refund failure to abort');
        } catch (\RuntimeException) {
            // expected
        }

        $this->assertDatabaseCount('refunds', 0);
        $this->assertDatabaseMissing('activity_logs', ['action' => 'refund.completed']);
    }

    public function test_the_reason_is_recorded_and_does_not_change_the_amount(): void
    {
        ['payment' => $payment] = $this->capturedPayment(['base_price' => 100]);

        $this->actingAs($this->admin())
            ->post(route('admin.payments.refund', $payment), ['reason' => 'Customer reported no-show'])
            ->assertRedirect();

        $refund = Refund::where('payment_id', $payment->id)->firstOrFail();
        $this->assertSame('Customer reported no-show', $refund->reason);
        $this->assertSame('100.00', $refund->amount);
    }

    public function test_re_payment_after_a_refund_is_still_rejected(): void
    {
        ['customer' => $customer, 'request' => $request, 'payment' => $payment] = $this->capturedPayment(['base_price' => 100]);
        $this->actingAs($this->admin())->post(route('admin.payments.refund', $payment))->assertRedirect();

        $this->actingAs($customer)
            ->from(route('customer.requests.show', $request))
            ->post(route('customer.payments.pay', $request))
            ->assertSessionHasErrors('payment');

        $this->assertDatabaseCount('payments', 1);
        $this->assertSame('100.00', $payment->fresh()->amount);
        $this->assertSame('paid', $payment->fresh()->status);
    }

    public function test_the_admin_console_shows_a_refund_action_for_a_paid_payment(): void
    {
        ['payment' => $payment] = $this->capturedPayment(['base_price' => 100]);

        $this->actingAs($this->admin())->get(route('admin.payments.index'))
            ->assertOk()
            ->assertSee(__('payments.refund_action'))
            ->assertSee(route('admin.payments.refund', $payment), false);
    }

    public function test_the_refund_action_disappears_once_the_payment_is_refunded(): void
    {
        ['payment' => $payment] = $this->capturedPayment(['base_price' => 100]);
        $this->actingAs($this->admin())->post(route('admin.payments.refund', $payment))->assertRedirect();

        $this->actingAs($this->admin())->get(route('admin.payments.index'))
            ->assertOk()
            ->assertSee(__('payments.refunded'))
            ->assertDontSee(route('admin.payments.refund', $payment), false);
    }

    public function test_the_customer_invoice_shows_a_refunded_state_without_changing_totals(): void
    {
        ['customer' => $customer, 'request' => $request, 'payment' => $payment] = $this->capturedPayment([
            'base_price' => 100, 'additional_charges' => 20, 'discount' => 10, 'tax' => 5,
        ]);
        $this->actingAs($this->admin())->post(route('admin.payments.refund', $payment))->assertRedirect();

        $this->actingAs($customer)->get(route('customer.requests.invoice', $request))
            ->assertOk()
            ->assertSee(__('status.payment.refunded'))
            ->assertSee(__('payments.refunded_notice_title'))
            ->assertSee('$115.00'); // total unchanged
    }

    public function test_worker_earnings_shows_refunded_and_retained_and_keeps_history(): void
    {
        ['worker' => $worker, 'payment' => $payment] = $this->capturedPayment(['base_price' => 100]);
        $this->actingAs($this->admin())->post(route('admin.payments.refund', $payment))->assertRedirect();

        $this->actingAs($worker)->get(route('worker.earnings'))
            ->assertOk()
            ->assertSee('$100.00')  // gross captured (unchanged)
            ->assertSee('$10.00')   // commission (unchanged)
            ->assertSee('$90.00')   // net at capture (unchanged)
            ->assertSee(__('payments.refunded'))
            ->assertSee(__('payments.retained'));

        $payment->refresh();
        $this->assertSame('90.00', $payment->worker_net);
        $this->assertSame('paid', $payment->status);
    }
}
