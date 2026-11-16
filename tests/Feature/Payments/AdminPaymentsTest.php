<?php

namespace Tests\Feature\Payments;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdminPaymentsTest extends TestCase
{
    use BuildsPaidJobs, RefreshDatabase;

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

    public function test_only_an_admin_can_open_the_payments_console(): void
    {
        $this->get(route('admin.payments.index'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->customer()->create())->get(route('admin.payments.index'))->assertForbidden();
        $this->actingAs(User::factory()->worker()->create())->get(route('admin.payments.index'))->assertForbidden();
        $this->actingAs($this->admin())->get(route('admin.payments.index'))->assertOk();
    }

    public function test_the_admin_sees_every_payment_with_its_frozen_commission(): void
    {
        $worker = User::factory()->worker()->create(['name' => 'Sam Solo']);
        $this->paid(['worker_id' => $worker->id, 'amount' => 300, 'commission_amount' => 30, 'worker_net' => 270]);

        $this->actingAs($this->admin())->get(route('admin.payments.index'))
            ->assertOk()
            ->assertSee('Sam Solo')
            ->assertSee('$300.00')
            ->assertSee('$30.00')
            ->assertSee('$270.00')
            ->assertSee('10%');
    }

    public function test_the_listing_is_paginated(): void
    {
        Payment::factory()->count(25)->paid()->create(['worker_id' => User::factory()->worker()]);

        $this->actingAs($this->admin())->get(route('admin.payments.index'))
            ->assertOk()
            ->assertSee('page=2', false);
    }

    public function test_the_summary_strip_aggregates_paid_payments_in_the_database(): void
    {
        $this->paid(['amount' => 100, 'commission_amount' => 10, 'worker_net' => 90]);
        $this->paid(['amount' => 300, 'commission_amount' => 30, 'worker_net' => 270]);
        // a non-paid payment must not count
        Payment::factory()->create(['status' => 'pending', 'amount' => 9999, 'commission_amount' => 999, 'worker_net' => 9000, 'worker_id' => User::factory()->worker()]);

        $response = $this->actingAs($this->admin())->get(route('admin.payments.index'))->assertOk();

        // Summary counts PAID payments only (100 + 300), not the pending 9,999.
        $response->assertSee('$400.00');  // gross volume
        $response->assertSee('$40.00');   // commission (10 + 30)
        $response->assertSee('$360.00');  // worker net (90 + 270)
        $response->assertSee('$200.00');  // average (400 / 2)
        // ...though the pending row is still listed in the table below.
        $response->assertSee('$9,999.00');
        $response->assertSee(__('status.payment.pending'));
    }

    public function test_status_filter_narrows_the_listing(): void
    {
        $this->paid(['amount' => 111]);
        Payment::factory()->create(['status' => 'pending', 'amount' => 222, 'worker_id' => User::factory()->worker()]);

        $paidOnly = $this->actingAs($this->admin())->get(route('admin.payments.index', ['status' => 'paid']))->assertOk();
        $paidOnly->assertSee('$111.00');
        $paidOnly->assertDontSee('$222.00');
    }

    public function test_worker_name_filter_narrows_the_listing(): void
    {
        $a = User::factory()->worker()->create(['name' => 'Alice Ampere']);
        $b = User::factory()->worker()->create(['name' => 'Bob Volt']);
        $this->paid(['worker_id' => $a->id, 'amount' => 140]);
        $this->paid(['worker_id' => $b->id, 'amount' => 260]);

        $response = $this->actingAs($this->admin())->get(route('admin.payments.index', ['worker' => 'Ampere']))->assertOk();
        $response->assertSee('Alice Ampere');
        $response->assertDontSee('Bob Volt');
        $response->assertSee('$140.00');
        $response->assertDontSee('$260.00');
    }

    public function test_date_range_filter_is_applied(): void
    {
        $this->paid(['amount' => 150, 'paid_at' => Carbon::parse('2026-03-15 10:00')]);
        $this->paid(['amount' => 950, 'paid_at' => Carbon::parse('2026-09-20 10:00')]);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.payments.index', ['from' => '2026-03-01', 'to' => '2026-03-31']))
            ->assertOk();

        $response->assertSee('$150.00');
        $response->assertDontSee('$950.00');
    }

    public function test_junk_query_parameters_are_ignored_and_do_not_error(): void
    {
        $this->paid(['amount' => 123]);

        $this->actingAs($this->admin())
            ->get(route('admin.payments.index', ['status' => 'nonsense', 'sort' => 'amount); drop table payments;--', 'order' => 'users.password']))
            ->assertOk()
            ->assertSee('$123.00');

        $this->assertDatabaseCount('payments', 1);
    }

    public function test_the_console_is_read_only_and_does_not_mutate_payments(): void
    {
        $payment = $this->paid(['amount' => 100, 'commission_rate' => '0.1000', 'commission_amount' => 10, 'worker_net' => 90]);
        $before = $payment->only(['amount', 'commission_rate', 'commission_amount', 'worker_net', 'worker_id', 'status']);

        $this->actingAs($this->admin())->get(route('admin.payments.index'))->assertOk();

        $this->assertSame($before, $payment->fresh()->only(array_keys($before)));
    }
}
