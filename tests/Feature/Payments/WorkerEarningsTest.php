<?php

namespace Tests\Feature\Payments;

use App\Models\Payment;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkerEarningsTest extends TestCase
{
    use BuildsPaidJobs, RefreshDatabase;

    private function paidPayment(User $worker, array $attrs = []): Payment
    {
        return Payment::factory()->paid()->create([
            'worker_id' => $worker->id,
            'amount' => 100,
            'commission_rate' => '0.1000',
            'commission_amount' => 10,
            'worker_net' => 90,
            ...$attrs,
        ]);
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        $this->get(route('worker.earnings'))->assertRedirect(route('login'));
    }

    public function test_a_customer_cannot_open_the_worker_earnings_page(): void
    {
        $this->actingAs(User::factory()->customer()->create())
            ->get(route('worker.earnings'))->assertForbidden();
    }

    public function test_an_admin_cannot_open_the_worker_earnings_page(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('worker.earnings'))->assertForbidden();
    }

    public function test_a_worker_only_ever_sees_their_own_payments(): void
    {
        $me = User::factory()->worker()->create();
        $other = User::factory()->worker()->create(['name' => 'Rival Rita']);

        $this->paidPayment($me, ['amount' => 200, 'commission_amount' => 20, 'worker_net' => 180]);
        $this->paidPayment($other, ['amount' => 999, 'commission_amount' => 99, 'worker_net' => 900]);

        $response = $this->actingAs($me)->get(route('worker.earnings'))->assertOk();

        $response->assertSee('$180.00');   // my net
        $response->assertDontSee('Rival Rita');
        $response->assertDontSee('$900.00'); // their net
        $response->assertDontSee('$999.00');
    }

    public function test_the_summary_aggregates_only_paid_payments_for_this_worker(): void
    {
        $me = User::factory()->worker()->create();

        $this->paidPayment($me, ['amount' => 100, 'commission_amount' => 10, 'worker_net' => 90]);
        $this->paidPayment($me, ['amount' => 250, 'commission_amount' => 25, 'worker_net' => 225]);
        // a pending payment for me — must be excluded
        Payment::factory()->create([
            'worker_id' => $me->id, 'status' => 'pending',
            'amount' => 5000, 'commission_amount' => 500, 'worker_net' => 4500,
        ]);

        $response = $this->actingAs($me)->get(route('worker.earnings'))->assertOk();

        $response->assertSee('$350.00');  // gross 100 + 250
        $response->assertSee('$35.00');   // commission 10 + 25
        $response->assertSee('$315.00');  // net 90 + 225
        $response->assertSee('>2<', false); // paid jobs count
        $response->assertDontSee('$5,000.00');
        $response->assertDontSee('$4,500.00');
    }

    public function test_historical_commission_is_shown_from_the_frozen_payment_not_the_live_rate(): void
    {
        $me = User::factory()->worker()->create();
        $this->paidPayment($me, ['amount' => 100, 'commission_rate' => '0.1000', 'commission_amount' => 10, 'worker_net' => 90]);

        // platform rate later changes
        PlatformSetting::setValue(PlatformSetting::COMMISSION_RATE, '0.1500');

        $response = $this->actingAs($me)->get(route('worker.earnings'))->assertOk();

        $response->assertSee('10%');       // frozen rate, not 15%
        $response->assertSee('$10.00');    // frozen commission
        $response->assertSee('$90.00');    // frozen net
        $response->assertDontSee('$15.00');
        $response->assertDontSee('$85.00');
    }

    public function test_a_worker_with_no_paid_jobs_sees_an_empty_state_and_zero_totals(): void
    {
        $me = User::factory()->worker()->create();

        $this->actingAs($me)->get(route('worker.earnings'))
            ->assertOk()
            ->assertSee(__('payments.no_paid_jobs'))
            ->assertSee('$0.00');
    }

    public function test_the_page_uses_a_real_captured_payment_end_to_end(): void
    {
        ['customer' => $customer, 'worker' => $worker, 'request' => $request] = $this->quotedCompletedJob([
            'base_price' => 100, 'additional_charges' => 20, 'discount' => 10, 'tax' => 5,
        ]);
        $this->actingAs($customer)->post(route('customer.payments.pay', $request))->assertRedirect();

        $this->actingAs($worker)->get(route('worker.earnings'))
            ->assertOk()
            ->assertSee('$115.00')   // gross
            ->assertSee('$11.50')    // commission
            ->assertSee('$103.50');  // net
    }
}
