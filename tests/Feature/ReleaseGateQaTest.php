<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Invoice;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\Quotation;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\WorkerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Final release gate: the frozen financial invariant end-to-end in both locales,
 * plus parameter-substitution / IDOR probes across the surfaces added in phases
 * 11–12. Kept as a permanent regression guard.
 */
class ReleaseGateQaTest extends TestCase
{
    use RefreshDatabase;

    private function fullPaidFlow(string $locale): array
    {
        $customer = User::factory()->customer()->create(['locale' => $locale]);
        $worker = User::factory()->worker()->create();
        $cat = ServiceCategory::factory()->create();
        $addr = Address::factory()->for($customer, 'user')->create();

        $req = ServiceRequest::factory()->for($customer, 'customer')->status('accepted')->create([
            'service_category_id' => $cat->id, 'worker_id' => $worker->id,
        ]);

        $this->actingAs($worker)->withSession(['locale' => $locale])
            ->post(route('worker.jobs.quotation', $req), ['base_price' => 100, 'additional_charges' => 20, 'discount' => 10, 'tax' => 5])
            ->assertRedirect();
        $quote = Quotation::where('service_request_id', $req->id)->firstOrFail();

        $this->actingAs($customer)->withSession(['locale' => $locale])
            ->post(route('customer.quotations.accept', $quote))->assertRedirect();

        $this->actingAs($worker)->post(route('worker.jobs.status', $req), ['status' => 'scheduled', 'scheduled_date' => now()->addDay()->toDateString(), 'scheduled_time' => '10:00'])->assertRedirect();
        foreach (['on_the_way', 'in_progress', 'completed'] as $s) {
            $this->actingAs($worker)->post(route('worker.jobs.status', $req), ['status' => $s])->assertRedirect();
        }

        $this->actingAs($customer)->withSession(['locale' => $locale])
            ->post(route('customer.payments.pay', $req))
            ->assertRedirect(route('customer.requests.receipt', $req));

        return [$customer, $worker, $req->fresh(), $quote->fresh(), Payment::where('service_request_id', $req->id)->firstOrFail(), Invoice::where('service_request_id', $req->id)->firstOrFail()];
    }

    private function assertInvariant(string $locale): void
    {
        [$customer, , $req, $quote, $payment, $invoice] = $this->fullPaidFlow($locale);

        $this->assertSame('115.00', $quote->total);
        $this->assertSame($quote->total, $req->final_total);
        $this->assertSame($quote->total, $payment->amount);
        $this->assertSame($quote->total, $invoice->total);

        // Receipt + invoice both render the same frozen figure in this locale.
        $this->actingAs($customer)->withSession(['locale' => $locale])
            ->get(route('customer.requests.receipt', $req))->assertOk()->assertSee('$115.00');
        $this->actingAs($customer)->withSession(['locale' => $locale])
            ->get(route('customer.requests.invoice', $req))->assertOk()->assertSee('$115.00');
    }

    public function test_financial_invariant_holds_end_to_end_in_english(): void
    {
        $this->assertInvariant('en');
    }

    public function test_financial_invariant_holds_end_to_end_in_arabic(): void
    {
        $this->assertInvariant('ar');
    }

    public function test_receipt_and_invoice_reject_a_foreign_request_id(): void
    {
        [, , $req] = $this->fullPaidFlow('en');
        $intruder = User::factory()->customer()->create();

        $this->actingAs($intruder)->get(route('customer.requests.receipt', $req))->assertForbidden();
        $this->actingAs($intruder)->get(route('customer.requests.invoice', $req))->assertForbidden();
    }

    public function test_notification_open_rejects_a_foreign_notification_id(): void
    {
        $a = User::factory()->customer()->create();
        $b = User::factory()->customer()->create();
        $n = Notification::create(['user_id' => $a->id, 'type' => 'request_status', 'title' => 'x', 'message' => 'y']);

        $this->actingAs($b)->get(route('notifications.open', $n->id))->assertNotFound();
    }

    public function test_message_poll_rejects_a_foreign_request_and_never_creates_messages(): void
    {
        $c = User::factory()->customer()->create();
        $w = User::factory()->worker()->create();
        $req = ServiceRequest::factory()->for($c, 'customer')->status('accepted')->create(['worker_id' => $w->id]);
        $stranger = User::factory()->customer()->create();

        $this->actingAs($stranger)->getJson(route('messages.poll', $req).'?after=0')->assertForbidden();
        $this->assertDatabaseCount('messages', 0);
    }

    public function test_admin_category_edit_rejects_non_admin_and_unsafe_icon(): void
    {
        $cat = ServiceCategory::factory()->create(['icon' => '🔧', 'slug' => 'keep-slug-xyz12']);
        $admin = User::factory()->admin()->create();

        $this->actingAs(User::factory()->worker()->create())->get(route('admin.services.edit', $cat))->assertForbidden();

        $this->actingAs($admin)->from(route('admin.services.edit', $cat))
            ->put(route('admin.services.update', $cat), ['name' => 'N', 'name_ar' => 'ن', 'base_price' => '10', 'icon' => '<img src=x onerror=alert(1)>'])
            ->assertSessionHasErrors('icon');

        $cat->refresh();
        $this->assertSame('🔧', $cat->icon);
        $this->assertSame('keep-slug-xyz12', $cat->slug);
    }

    public function test_address_update_cannot_rebind_owner(): void
    {
        $owner = User::factory()->customer()->create();
        $other = User::factory()->customer()->create();
        $addr = Address::factory()->for($owner, 'user')->create();

        $this->actingAs($owner)->put(route('customer.addresses.update', $addr), [
            'title' => 'x', 'street' => 's', 'city' => 'c', 'user_id' => $other->id,
        ])->assertRedirect();

        $this->assertSame($owner->id, $addr->fresh()->user_id);
    }

    public function test_csv_export_rows_match_the_status_filter(): void
    {
        $admin = User::factory()->admin()->create();
        Payment::factory()->paid()->create(['transaction_reference' => 'KEEP-PAID', 'amount' => 50, 'commission_amount' => 5, 'worker_net' => 45, 'worker_id' => User::factory()->worker()]);
        Payment::factory()->create(['status' => 'failed', 'transaction_reference' => 'DROP-FAILED', 'amount' => 9, 'commission_amount' => 1, 'worker_net' => 8, 'worker_id' => User::factory()->worker()]);

        $body = $this->actingAs($admin)->get(route('admin.payments.export', ['status' => 'paid']))->streamedContent();
        $this->assertStringContainsString('KEEP-PAID', $body);
        $this->assertStringNotContainsString('DROP-FAILED', $body);
    }

    public function test_suspended_login_shows_screen_only_with_correct_password(): void
    {
        $u = User::factory()->customer()->create(['password' => 'secret-pw-123']);
        $u->update(['status' => 'suspended']);

        $this->post(route('login.store'), ['email' => $u->email, 'password' => 'secret-pw-123'])
            ->assertRedirect(route('account.suspended'));
        $this->post(route('login.store'), ['email' => $u->email, 'password' => 'wrong'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_profileless_worker_dashboard_is_the_only_known_soft_spot(): void
    {
        // Real registration path: assert a worker signup creates a WorkerProfile.
        $this->post(route('register.store'), [
            'name' => 'New Pro', 'email' => 'newpro@example.com', 'phone' => '+96170123456',
            'password' => 'Password@123', 'password_confirmation' => 'Password@123', 'role' => 'worker',
        ])->assertRedirect();

        $worker = User::where('email', 'newpro@example.com')->firstOrFail();
        $this->assertNotNull($worker->workerProfile, 'registration must create a WorkerProfile');
        $this->actingAs($worker)->get(route('worker.dashboard'))->assertOk();
    }
}
