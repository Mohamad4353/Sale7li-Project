<?php

namespace Tests\Feature\Admin;

use App\Models\Payment;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * P10-14 — admin CSV exports. Read-only, admin-only, streamed, escaped, and
 * built from the same filtered query the on-screen page uses.
 */
class AdminCsvExportTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function paid(array $attrs = []): Payment
    {
        return Payment::factory()->paid()->create([
            'amount' => 100, 'commission_rate' => '0.1000', 'commission_amount' => 10, 'worker_net' => 90,
            'worker_id' => User::factory()->worker(),
            ...$attrs,
        ]);
    }

    private function body(TestResponse $response): string
    {
        return $response->streamedContent();
    }

    // ---- Payments export -------------------------------------------------

    public function test_payments_export_is_admin_only(): void
    {
        $this->get(route('admin.payments.export'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->customer()->create())->get(route('admin.payments.export'))->assertForbidden();
        $this->actingAs(User::factory()->worker()->create())->get(route('admin.payments.export'))->assertForbidden();
        $this->actingAs($this->admin())->get(route('admin.payments.export'))->assertOk();
    }

    public function test_payments_export_streams_a_csv_download_with_matching_rows(): void
    {
        $worker = User::factory()->worker()->create(['name' => 'Wanda Wire']);
        $this->paid(['worker_id' => $worker->id, 'amount' => 250, 'commission_amount' => 25, 'worker_net' => 225]);

        $response = $this->actingAs($this->admin())->get(route('admin.payments.export'))->assertOk();

        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
        $this->assertStringContainsString('attachment', $response->headers->get('content-disposition'));

        $body = $this->body($response);
        $this->assertStringContainsString('Wanda Wire', $body);
        $this->assertStringContainsString('250.00', $body);
        $this->assertStringContainsString(__('payments.col_worker'), $body); // localized heading
    }

    public function test_payments_export_respects_the_status_filter(): void
    {
        $this->paid(['transaction_reference' => 'TEST-PAIDROW']);
        Payment::factory()->create([
            'status' => 'failed', 'transaction_reference' => 'TEST-FAILEDROW',
            'amount' => 40, 'commission_amount' => 4, 'worker_net' => 36,
            'worker_id' => User::factory()->worker(),
        ]);

        $body = $this->body(
            $this->actingAs($this->admin())->get(route('admin.payments.export', ['status' => 'failed']))->assertOk()
        );

        $this->assertStringContainsString('TEST-FAILEDROW', $body);
        $this->assertStringNotContainsString('TEST-PAIDROW', $body);
    }

    public function test_payments_export_never_exposes_secrets(): void
    {
        $this->paid();
        $body = $this->body($this->actingAs($this->admin())->get(route('admin.payments.export'))->assertOk());

        $this->assertStringNotContainsString('password', strtolower($body));
        $this->assertStringNotContainsString('remember_token', strtolower($body));
    }

    public function test_payments_export_neutralises_csv_formula_injection(): void
    {
        $worker = User::factory()->worker()->create(['name' => '=cmd|/c calc']);
        $this->paid(['worker_id' => $worker->id]);

        $body = $this->body($this->actingAs($this->admin())->get(route('admin.payments.export'))->assertOk());

        // The dangerous leading "=" must be defused, never emitted bare.
        $this->assertStringContainsString("'=cmd|/c calc", $body);
    }

    public function test_payments_export_headings_are_localized_in_arabic(): void
    {
        $this->paid();
        $body = $this->body(
            $this->actingAs($this->admin())->withSession(['locale' => 'ar'])
                ->get(route('admin.payments.export'))->assertOk()
        );

        $this->assertStringContainsString(__('payments.col_worker', [], 'ar'), $body);
    }

    // ---- Requests export ----------------------------------------------------

    public function test_requests_export_is_admin_only(): void
    {
        $this->get(route('admin.requests.export'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->customer()->create())->get(route('admin.requests.export'))->assertForbidden();
        $this->actingAs($this->admin())->get(route('admin.requests.export'))->assertOk();
    }

    public function test_requests_export_respects_the_queue_filter(): void
    {
        $unassigned = ServiceRequest::factory()->status('pending')->create(['reference' => 'SL-UNASSIGNED', 'worker_id' => null]);
        $assigned = ServiceRequest::factory()->status('accepted')->create([
            'reference' => 'SL-ASSIGNED', 'worker_id' => User::factory()->worker()->create()->id,
        ]);

        $body = $this->body(
            $this->actingAs($this->admin())->get(route('admin.requests.export', ['queue' => 'unassigned']))->assertOk()
        );

        $this->assertStringContainsString('SL-UNASSIGNED', $body);
        $this->assertStringNotContainsString('SL-ASSIGNED', $body);
    }

    // ---- Reconciliation export -------------------------------------------

    public function test_reconciliation_export_is_admin_only(): void
    {
        $this->get(route('admin.payments.reconciliation.export'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->customer()->create())->get(route('admin.payments.reconciliation.export'))->assertForbidden();
        $this->actingAs($this->admin())->get(route('admin.payments.reconciliation.export'))->assertOk();
    }

    public function test_reconciliation_export_lists_detected_discrepancies(): void
    {
        // A paid payment whose service request has no invoice => missing_invoice.
        $request = ServiceRequest::factory()->status('completed')->create(['reference' => 'SL-BROKEN']);
        Payment::factory()->paid()->create([
            'service_request_id' => $request->id,
            'amount' => 100, 'commission_amount' => 10, 'worker_net' => 90,
            'worker_id' => User::factory()->worker(),
        ]);

        $body = $this->body(
            $this->actingAs($this->admin())->get(route('admin.payments.reconciliation.export'))->assertOk()
        );

        $this->assertStringContainsString('SL-BROKEN', $body);
        $this->assertStringContainsString(__('payments.recon_code_missing_invoice'), $body);
    }
}
