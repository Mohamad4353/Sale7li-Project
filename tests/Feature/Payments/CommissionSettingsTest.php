<?php

namespace Tests\Feature\Payments;

use App\Models\Payment;
use App\Models\User;
use App\Payments\CommissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommissionSettingsTest extends TestCase
{
    use BuildsPaidJobs, RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_only_an_admin_can_reach_the_commission_settings(): void
    {
        $this->get(route('admin.settings.commission.edit'))->assertRedirect(route('login'));
        $this->put(route('admin.settings.commission.update'), ['rate' => '0.2000'])->assertRedirect(route('login'));

        foreach (['customer', 'worker'] as $role) {
            $user = User::factory()->{$role}()->create();
            $this->actingAs($user)->get(route('admin.settings.commission.edit'))->assertForbidden();
            $this->actingAs($user)->put(route('admin.settings.commission.update'), ['rate' => '0.2000'])->assertForbidden();
        }

        $this->assertSame('0.1000', app(CommissionService::class)->activeRate());
    }

    public function test_the_edit_page_shows_the_current_rate(): void
    {
        $this->actingAs($this->admin())->get(route('admin.settings.commission.edit'))
            ->assertOk()
            ->assertSee('10%')
            ->assertSee('0.1000');
    }

    public function test_an_admin_can_change_the_rate_and_it_is_stored_canonically(): void
    {
        $this->actingAs($this->admin())
            ->put(route('admin.settings.commission.update'), ['rate' => '0.15'])
            ->assertRedirect(route('admin.settings.commission.edit'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('platform_settings', ['key' => 'commission_rate', 'value' => '0.1500']);
        $this->assertSame('0.1500', app(CommissionService::class)->activeRate());
    }

    /** @dataProvider invalidRates */
    public function test_invalid_rates_are_rejected(string $rate): void
    {
        $this->actingAs($this->admin())
            ->from(route('admin.settings.commission.edit'))
            ->put(route('admin.settings.commission.update'), ['rate' => $rate])
            ->assertSessionHasErrors('rate');

        $this->assertSame('0.1000', app(CommissionService::class)->activeRate());
    }

    public static function invalidRates(): array
    {
        return [['0.9001'], ['1.0000'], ['-0.1000'], ['0.12345'], ['abc'], ['']];
    }

    /** @dataProvider validRates */
    public function test_valid_rates_are_accepted(string $input, string $stored): void
    {
        $this->actingAs($this->admin())
            ->put(route('admin.settings.commission.update'), ['rate' => $input])
            ->assertSessionHasNoErrors();

        $this->assertSame($stored, app(CommissionService::class)->activeRate());
    }

    public static function validRates(): array
    {
        return [
            ['0', '0.0000'],
            ['0.0000', '0.0000'],
            ['0.15', '0.1500'],
            ['0.9', '0.9000'],
            ['0.9000', '0.9000'],
        ];
    }

    public function test_changing_the_rate_writes_an_activity_log_entry(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.settings.commission.update'), ['rate' => '0.2000'])->assertRedirect();

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'action' => 'commission_rate.updated',
        ]);
    }

    public function test_historical_payments_are_untouched_when_the_rate_changes(): void
    {
        ['customer' => $customer, 'request' => $request] = $this->quotedCompletedJob(['base_price' => 100]);
        $this->actingAs($customer)->post(route('customer.payments.pay', $request))->assertRedirect();

        $payment = Payment::where('service_request_id', $request->id)->firstOrFail();
        $this->assertSame('0.1000', $payment->commission_rate);
        $this->assertSame('10.00', $payment->commission_amount);
        $this->assertSame('90.00', $payment->worker_net);

        $this->actingAs($this->admin())->put(route('admin.settings.commission.update'), ['rate' => '0.2500'])->assertRedirect();

        $payment->refresh();
        $this->assertSame('0.1000', $payment->commission_rate);
        $this->assertSame('10.00', $payment->commission_amount);
        $this->assertSame('90.00', $payment->worker_net);
    }

    public function test_a_new_payment_after_the_change_uses_the_new_rate(): void
    {
        $this->actingAs($this->admin())->put(route('admin.settings.commission.update'), ['rate' => '0.2500'])->assertRedirect();

        ['customer' => $customer, 'request' => $request] = $this->quotedCompletedJob(['base_price' => 100]);
        $this->actingAs($customer)->post(route('customer.payments.pay', $request))->assertRedirect();

        $payment = Payment::where('service_request_id', $request->id)->firstOrFail();
        $this->assertSame('0.2500', $payment->commission_rate);
        $this->assertSame('25.00', $payment->commission_amount);
        $this->assertSame('75.00', $payment->worker_net);
    }
}
