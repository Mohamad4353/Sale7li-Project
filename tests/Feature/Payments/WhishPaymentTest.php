<?php

namespace Tests\Feature\Payments;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Feature 2 — WHISH manual payment. A customer submits a WHISH reference (+ an
 * optional proof); the payment sits `pending` until an admin approves it, at
 * which point it settles through the SAME capture path as the instant flow, so
 * quotation.total == invoice.total == payment.amount == final_total holds.
 */
class WhishPaymentTest extends TestCase
{
    use BuildsPaidJobs, RefreshDatabase;

    private function submitWhish(User $customer, ServiceRequest $request, array $payload = []): void
    {
        $this->actingAs($customer)
            ->post(route('customer.payments.whish.submit', $request), [
                'whish_reference' => 'WISH-REF-12345',
                ...$payload,
            ])
            ->assertRedirect(route('customer.requests.show', $request));
    }

    /** A real 1x1 PNG — no GD needed, unlike UploadedFile::fake()->image(). */
    private function realImage(string $name = 'transfer.png'): UploadedFile
    {
        return new UploadedFile(base_path('tests/Fixtures/pixel.png'), $name, 'image/png', null, true);
    }

    public function test_customer_can_open_the_whish_screen(): void
    {
        ['customer' => $customer, 'request' => $request] = $this->quotedAcceptedJob(['base_price' => 120]);

        $this->actingAs($customer)->get(route('customer.payments.whish', $request))
            ->assertOk()
            ->assertSee('$120.00')
            ->assertSee(config('sale7li.whish.account_number'));
    }

    public function test_submitting_a_reference_creates_a_pending_whish_payment_and_notifies_the_customer(): void
    {
        ['customer' => $customer, 'worker' => $worker, 'request' => $request, 'quotation' => $quote]
            = $this->quotedAcceptedJob(['base_price' => 100]);

        $this->submitWhish($customer, $request);

        $payment = Payment::where('service_request_id', $request->id)->firstOrFail();
        $this->assertSame('whish', $payment->payment_method);
        $this->assertSame('pending', $payment->status);
        $this->assertSame('WISH-REF-12345', $payment->whish_reference);
        $this->assertSame($quote->total, $payment->amount);          // server-derived, not input
        $this->assertSame($customer->id, $payment->customer_id);
        $this->assertSame($worker->id, $payment->worker_id);
        $this->assertNotNull($payment->submitted_at);
        $this->assertNull($payment->paid_at);
        $this->assertNull($payment->commission_amount);

        // The request is NOT paid and no invoice exists yet.
        $this->assertNotSame('paid', $request->fresh()->payment_status);
        $this->assertDatabaseCount('invoices', 0);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $customer->id,
            'type' => 'payment_submitted',
            'service_request_id' => $request->id,
        ]);
    }

    public function test_customer_can_attach_a_valid_proof_stored_on_the_private_disk(): void
    {
        Storage::fake('local');
        ['customer' => $customer, 'request' => $request] = $this->quotedAcceptedJob();

        $this->submitWhish($customer, $request, ['proof' => $this->realImage()]);

        $payment = Payment::where('service_request_id', $request->id)->firstOrFail();
        $this->assertNotNull($payment->payment_proof_path);
        $this->assertStringStartsWith('payment-proofs/', $payment->payment_proof_path);
        Storage::disk('local')->assertExists($payment->payment_proof_path);
    }

    public function test_an_executable_proof_upload_is_rejected(): void
    {
        ['customer' => $customer, 'request' => $request] = $this->quotedAcceptedJob();

        $this->actingAs($customer)->from(route('customer.payments.whish', $request))
            ->post(route('customer.payments.whish.submit', $request), [
                'whish_reference' => 'WISH-REF-12345',
                'proof' => UploadedFile::fake()->create('evil.php', 8, 'application/x-httpd-php'),
            ])
            ->assertSessionHasErrors('proof');

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_whish_reference_is_required_and_sanitised(): void
    {
        ['customer' => $customer, 'request' => $request] = $this->quotedAcceptedJob();

        $this->actingAs($customer)->from(route('customer.payments.whish', $request))
            ->post(route('customer.payments.whish.submit', $request), ['whish_reference' => ''])
            ->assertSessionHasErrors('whish_reference');

        $this->actingAs($customer)->from(route('customer.payments.whish', $request))
            ->post(route('customer.payments.whish.submit', $request), ['whish_reference' => '<script>alert(1)</script>'])
            ->assertSessionHasErrors('whish_reference');

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_pending_whish_payment_shows_in_the_admin_queue_only_for_admins(): void
    {
        ['customer' => $customer, 'request' => $request] = $this->quotedAcceptedJob(['base_price' => 90]);

        $this->app['auth']->forgetGuards();
        $this->get(route('admin.payments.whish'))->assertRedirect(route('login'));

        $this->submitWhish($customer, $request);

        $this->actingAs($customer)->get(route('admin.payments.whish'))->assertForbidden();
        $this->actingAs(User::factory()->worker()->create())->get(route('admin.payments.whish'))->assertForbidden();

        $this->actingAs(User::factory()->admin()->create())->get(route('admin.payments.whish'))
            ->assertOk()
            ->assertSee($request->reference)
            ->assertSee('WISH-REF-12345')
            ->assertSee('$90.00');
    }

    public function test_admin_approval_settles_through_the_shared_capture_path_and_preserves_the_invariant(): void
    {
        ['customer' => $customer, 'worker' => $worker, 'request' => $request, 'quotation' => $quote]
            = $this->quotedCompletedJob(['base_price' => 100, 'additional_charges' => 20, 'discount' => 10, 'tax' => 5]);

        $this->submitWhish($customer, $request);
        $payment = Payment::where('service_request_id', $request->id)->firstOrFail();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.payments.whish.approve', $payment))->assertRedirect();

        $payment->refresh();
        $request->refresh();
        $invoice = Invoice::where('service_request_id', $request->id)->firstOrFail();

        $this->assertSame('paid', $payment->status);
        $this->assertSame($admin->id, $payment->verified_by);
        $this->assertNotNull($payment->verified_at);
        $this->assertNotNull($payment->paid_at);
        $this->assertNotNull($payment->commission_amount);
        $this->assertNotNull($payment->worker_net);
        $this->assertSame('paid', $request->payment_status);

        // The frozen four-way invariant.
        $this->assertSame('115.00', $quote->fresh()->total);
        $this->assertSame($quote->fresh()->total, $invoice->total);
        $this->assertSame($quote->fresh()->total, $payment->amount);
        $this->assertSame($quote->fresh()->total, $request->final_total);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $customer->id, 'type' => 'whish_payment_approved', 'service_request_id' => $request->id,
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $worker->id, 'type' => 'payment_confirmed', 'service_request_id' => $request->id,
        ]);
    }

    public function test_admin_rejection_records_a_reason_and_never_marks_the_request_paid(): void
    {
        ['customer' => $customer, 'request' => $request] = $this->quotedAcceptedJob();
        $this->submitWhish($customer, $request);
        $payment = Payment::where('service_request_id', $request->id)->firstOrFail();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.payments.whish.reject', $payment), [
            'reason' => 'Reference not found in the WHISH statement.',
        ])->assertRedirect();

        $payment->refresh();
        $this->assertSame('failed', $payment->status);
        $this->assertSame('Reference not found in the WHISH statement.', $payment->rejection_reason);
        $this->assertSame($admin->id, $payment->verified_by);
        $this->assertNotNull($payment->verified_at);
        $this->assertNotSame('paid', $request->fresh()->payment_status);
        $this->assertDatabaseCount('invoices', 0);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $customer->id, 'type' => 'whish_payment_rejected', 'service_request_id' => $request->id,
        ]);
    }

    public function test_a_customer_can_never_approve_or_reject_a_whish_payment(): void
    {
        ['customer' => $customer, 'request' => $request] = $this->quotedAcceptedJob();
        $this->submitWhish($customer, $request);
        $payment = Payment::where('service_request_id', $request->id)->firstOrFail();

        $this->actingAs($customer)->post(route('admin.payments.whish.approve', $payment))->assertForbidden();
        $this->actingAs($customer)->post(route('admin.payments.whish.reject', $payment))->assertForbidden();
        $this->actingAs(User::factory()->worker()->create())->post(route('admin.payments.whish.approve', $payment))->assertForbidden();

        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_a_stranger_cannot_submit_a_whish_payment_for_someone_elses_request(): void
    {
        ['request' => $request] = $this->quotedAcceptedJob();
        $stranger = User::factory()->customer()->create();

        $this->actingAs($stranger)
            ->post(route('customer.payments.whish.submit', $request), ['whish_reference' => 'WISH-REF-12345'])
            ->assertForbidden();

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_payment_proof_is_private_to_the_payer_and_admins(): void
    {
        Storage::fake('local');
        ['customer' => $customer, 'worker' => $worker, 'request' => $request] = $this->quotedAcceptedJob();
        $this->submitWhish($customer, $request, ['proof' => $this->realImage()]);
        $payment = Payment::where('service_request_id', $request->id)->firstOrFail();

        $this->app['auth']->forgetGuards();
        $this->get(route('attachments.paymentProof', $payment))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->customer()->create())->get(route('attachments.paymentProof', $payment))->assertForbidden();
        $this->actingAs($worker)->get(route('attachments.paymentProof', $payment))->assertForbidden();
        $this->actingAs($customer)->get(route('attachments.paymentProof', $payment))->assertOk();
        $this->actingAs(User::factory()->admin()->create())->get(route('attachments.paymentProof', $payment))->assertOk();
    }

    public function test_a_rejected_customer_can_submit_a_fresh_whish_payment(): void
    {
        ['customer' => $customer, 'request' => $request] = $this->quotedAcceptedJob();
        $this->submitWhish($customer, $request);
        $payment = Payment::where('service_request_id', $request->id)->firstOrFail();
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.payments.whish.reject', $payment), ['reason' => 'nope'])->assertRedirect();

        $this->submitWhish($customer, $request, ['whish_reference' => 'WISH-REF-99999']);

        $payment->refresh();
        $this->assertSame('pending', $payment->status);
        $this->assertSame('WISH-REF-99999', $payment->whish_reference);
        $this->assertNull($payment->rejection_reason);
        $this->assertNull($payment->verified_at);
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_whish_screen_and_flow_render_in_arabic(): void
    {
        ['customer' => $customer, 'request' => $request] = $this->quotedAcceptedJob();

        $this->actingAs($customer)->withSession(['locale' => 'ar'])
            ->get(route('customer.payments.whish', $request))
            ->assertOk()
            ->assertSee(__('payments.whish_title', [], 'ar'))
            ->assertDontSee(__('payments.whish_title', [], 'en'));
    }
}
