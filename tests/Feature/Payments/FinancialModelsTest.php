<?php

namespace Tests\Feature\Payments;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_factory_builds_pending_paid_and_refunded_states(): void
    {
        $this->assertSame('pending', Payment::factory()->create()->status);

        $paid = Payment::factory()->paid()->create();
        $this->assertSame('paid', $paid->status);
        $this->assertNotNull($paid->paid_at);

        $this->assertSame('refunded', Payment::factory()->refunded()->create()->status);
    }

    public function test_payment_belongs_to_a_worker(): void
    {
        $worker = User::factory()->worker()->create();
        $payment = Payment::factory()->for($worker, 'worker')->create();

        $this->assertTrue($payment->worker->is($worker));
    }

    public function test_payment_financial_fields_are_cast(): void
    {
        $payment = Payment::factory()->create([
            'amount' => 100,
            'commission_rate' => '0.1000',
            'commission_amount' => 10,
            'worker_net' => 90,
        ]);
        $payment->refresh();

        $this->assertSame('100.00', $payment->amount);
        $this->assertSame('0.1000', $payment->commission_rate);
        $this->assertSame('10.00', $payment->commission_amount);
        $this->assertSame('90.00', $payment->worker_net);
    }

    public function test_new_payment_columns_are_nullable_for_historical_rows(): void
    {
        $payment = Payment::factory()->create();

        $this->assertNull($payment->worker_id);
        $this->assertNull($payment->commission_rate);
        $this->assertNull($payment->commission_amount);
        $this->assertNull($payment->worker_net);
    }

    public function test_only_one_payment_per_service_request_is_allowed_by_the_database(): void
    {
        $request = ServiceRequest::factory()->create();
        Payment::factory()->create(['service_request_id' => $request->id]);

        $this->expectException(QueryException::class);
        Payment::factory()->create(['service_request_id' => $request->id]);
    }

    public function test_invoice_factory_creates_a_valid_record(): void
    {
        $invoice = Invoice::factory()->paid()->create();

        $this->assertSame('paid', $invoice->payment_status);
        $this->assertIsArray($invoice->line_items);
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id]);
    }

    public function test_locked_amount_prefers_final_total_then_quoted_total_then_null(): void
    {
        $both = ServiceRequest::factory()->create(['quoted_total' => 80, 'final_total' => 120]);
        $this->assertSame('120.00', $both->lockedAmount());

        $quotedOnly = ServiceRequest::factory()->create(['quoted_total' => 80, 'final_total' => null]);
        $this->assertSame('80.00', $quotedOnly->lockedAmount());

        $neither = ServiceRequest::factory()->create(['quoted_total' => null, 'final_total' => null]);
        $this->assertNull($neither->lockedAmount());
    }
}
