<?php

namespace Tests\Feature\Payments;

use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Guards the eager-loading on the read-heavy financial pages: the query count
 * must not scale with the number of rows on the page.
 */
class FinancialQueryBudgetTest extends TestCase
{
    use BuildsPaidJobs, RefreshDatabase;

    private function seedPaidPayments(int $count, ?User $worker = null): void
    {
        for ($i = 0; $i < $count; $i++) {
            $payment = Payment::factory()->paid()->create([
                'worker_id' => $worker?->id ?? User::factory()->worker(),
                'customer_id' => User::factory()->customer(),
                'amount' => 100 + $i,
                'commission_rate' => '0.1000',
                'commission_amount' => 10,
                'worker_net' => 90 + $i,
            ]);
            if ($i % 2 === 0) {
                Refund::factory()->ofPayment($payment)->create();
            }
        }
    }

    private function countQueries(callable $fn): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $fn();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    public function test_admin_payments_index_does_not_run_a_query_per_row(): void
    {
        $admin = User::factory()->admin()->create();
        $this->seedPaidPayments(3);
        $small = $this->countQueries(fn () => $this->actingAs($admin)->get(route('admin.payments.index'))->assertOk());

        $this->seedPaidPayments(15);
        $large = $this->countQueries(fn () => $this->actingAs($admin)->get(route('admin.payments.index'))->assertOk());

        $this->assertLessThanOrEqual($small + 2, $large, "admin payments went from {$small} to {$large} queries");
        $this->assertLessThanOrEqual(25, $large);
    }

    public function test_worker_earnings_does_not_run_a_query_per_row(): void
    {
        $worker = User::factory()->worker()->create();

        $this->seedPaidPayments(3, $worker);
        $small = $this->countQueries(fn () => $this->actingAs($worker)->get(route('worker.earnings'))->assertOk());

        $this->seedPaidPayments(12, $worker);
        $large = $this->countQueries(fn () => $this->actingAs($worker)->get(route('worker.earnings'))->assertOk());

        $this->assertLessThanOrEqual($small + 2, $large, "worker earnings went from {$small} to {$large} queries");
        $this->assertLessThanOrEqual(25, $large);
    }

    public function test_a_single_refund_capture_is_constant_time_regardless_of_refund_history(): void
    {
        $admin = User::factory()->admin()->create();

        // lots of unrelated refunds in the system
        $this->seedPaidPayments(20);

        ['customer' => $customer, 'request' => $request] = $this->quotedCompletedJob(['base_price' => 100]);
        $this->actingAs($customer)->post(route('customer.payments.pay', $request))->assertRedirect();
        $payment = Payment::where('service_request_id', $request->id)->firstOrFail();

        $queries = $this->countQueries(
            fn () => $this->actingAs($admin)->post(route('admin.payments.refund', $payment))->assertRedirect()
        );

        $this->assertLessThanOrEqual(15, $queries, "refund capture ran {$queries} queries");
        $this->assertDatabaseHas('refunds', ['payment_id' => $payment->id, 'status' => 'completed']);
    }
}
