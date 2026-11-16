<?php

namespace Tests\Feature\Payments;

use App\Models\PlatformSetting;
use App\Payments\CommissionService;
use App\Payments\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class CommissionCalculationTest extends TestCase
{
    use RefreshDatabase;

    private function service(): CommissionService
    {
        return app(CommissionService::class);
    }

    public function test_the_default_platform_commission_rate_is_ten_percent(): void
    {
        $this->assertSame('0.1000', $this->service()->activeRate());
        $this->assertDatabaseHas('platform_settings', ['key' => 'commission_rate', 'value' => '0.1000']);
    }

    public function test_ten_percent_commission_is_calculated_correctly(): void
    {
        $result = $this->service()->calculate('100.00');

        $this->assertSame(10000, $result->grossCents);
        $this->assertSame('0.1000', $result->rate);
        $this->assertSame(1000, $result->commissionCents);
        $this->assertSame(9000, $result->netCents);
        $this->assertSame('10.00', $result->commissionAmount());
        $this->assertSame('90.00', $result->workerNetAmount());
    }

    public function test_zero_commission_leaves_the_whole_amount_with_the_worker(): void
    {
        PlatformSetting::setValue(PlatformSetting::COMMISSION_RATE, '0.0000');

        $result = $this->service()->calculate('100.00');

        $this->assertSame(0, $result->commissionCents);
        $this->assertSame(10000, $result->netCents);
        $this->assertSame('0.00', $result->commissionAmount());
        $this->assertSame('100.00', $result->workerNetAmount());
    }

    public function test_the_maximum_ninety_percent_commission_is_allowed(): void
    {
        $result = $this->service()->calculate('100.00', '0.9000');

        $this->assertSame(9000, $result->commissionCents);
        $this->assertSame(1000, $result->netCents);
        $this->assertSame('90.00', $result->commissionAmount());
        $this->assertSame('10.00', $result->workerNetAmount());
    }

    public function test_a_rate_above_ninety_percent_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service()->calculate('100.00', '0.9001');
    }

    public function test_a_negative_rate_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service()->calculate('100.00', '-0.1000');
    }

    public function test_a_stored_rate_above_the_limit_is_rejected_on_read(): void
    {
        PlatformSetting::setValue(PlatformSetting::COMMISSION_RATE, '1.0000');

        $this->expectException(InvalidArgumentException::class);
        $this->service()->activeRate();
    }

    public function test_worker_net_always_equals_gross_minus_commission(): void
    {
        foreach (['0.0000', '0.1000', '0.1500', '0.3333', '0.9000'] as $rate) {
            foreach (['0.00', '0.01', '0.05', '0.99', '100.00', '99.99', '12345.67'] as $gross) {
                $r = $this->service()->calculate($gross, $rate);
                $this->assertSame($r->grossCents - $r->commissionCents, $r->netCents, "$gross @ $rate");
                $this->assertGreaterThanOrEqual(0, $r->netCents);
                $this->assertLessThanOrEqual($r->grossCents, $r->commissionCents);
            }
        }
    }

    public function test_commission_rounds_half_up_to_the_nearest_cent(): void
    {
        // 10% of 5c = 0.5c -> half up -> 1c ; 10% of 15c = 1.5c -> 2c
        $this->assertSame(1, $this->service()->calculate('0.05')->commissionCents);
        $this->assertSame(2, $this->service()->calculate('0.15')->commissionCents);

        // just below the half rounds down
        $this->assertSame(1, $this->service()->calculate('0.14')->commissionCents);
        // just above rounds up
        $this->assertSame(2, $this->service()->calculate('0.16')->commissionCents);

        // 10% of 999.9c ($99.99) = 999.9c -> half up -> 1000c
        $this->assertSame(1000, $this->service()->calculate('99.99')->commissionCents);
        $this->assertSame(8999, $this->service()->calculate('99.99')->netCents);
    }

    public function test_decimal_cent_edge_cases(): void
    {
        // gross 0 -> everything is 0
        $zero = $this->service()->calculate('0.00');
        $this->assertSame(0, $zero->commissionCents);
        $this->assertSame(0, $zero->netCents);

        // $0.09 @ 10% = 0.9c -> half up -> 1c ; net 8c
        $this->assertSame(1, $this->service()->calculate('0.09')->commissionCents);
        $this->assertSame(8, $this->service()->calculate('0.09')->netCents);

        // $0.04 @ 10% = 0.4c -> down -> 0c ; net 4c
        $this->assertSame(0, $this->service()->calculate('0.04')->commissionCents);
        $this->assertSame(4, $this->service()->calculate('0.04')->netCents);

        // large value
        $big = $this->service()->calculate('1234567.89');
        $this->assertSame(123456789, $big->grossCents);
        $this->assertSame('123456.79', $big->commissionAmount());
        $this->assertSame('1111111.10', $big->workerNetAmount());
    }

    public function test_money_parsing_never_uses_floating_point(): void
    {
        // A float-based parser (int)((float)"99.99" * 100) yields 9998.
        $this->assertSame(9999, Money::toCents('99.99'));
        $this->assertSame(10, Money::toCents('0.10'));
        $this->assertSame(29, Money::toCents('0.29'));
        $this->assertSame(123456789, Money::toCents('1234567.89'));
        $this->assertSame(10000, Money::toCents(100));
        $this->assertSame('90.00', Money::toDecimalString(9000));
        $this->assertSame('0.05', Money::toDecimalString(5));
    }

    public function test_negative_money_input_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::toCents('-1.00');
    }

    public function test_changing_the_platform_setting_changes_new_calculations(): void
    {
        $before = $this->service()->calculate('100.00');
        $this->assertSame('10.00', $before->commissionAmount());

        PlatformSetting::setValue(PlatformSetting::COMMISSION_RATE, '0.2000');

        $after = $this->service()->calculate('100.00');
        $this->assertSame('0.2000', $after->rate);
        $this->assertSame('20.00', $after->commissionAmount());
        $this->assertSame('80.00', $after->workerNetAmount());

        // the earlier result object is unchanged (deterministic, immutable)
        $this->assertSame('10.00', $before->commissionAmount());
    }

    public function test_the_result_is_deterministic_for_the_same_inputs(): void
    {
        $a = $this->service()->calculate('87.33', '0.1500');
        $b = $this->service()->calculate('87.33', '0.1500');

        $this->assertEquals($a->toArray(), $b->toArray());
        $this->assertSame($a->commissionCents, $b->commissionCents);
        $this->assertSame($a->netCents, $b->netCents);
    }

    public function test_rate_input_precision_is_canonicalised_to_four_decimals(): void
    {
        PlatformSetting::setValue(PlatformSetting::COMMISSION_RATE, '0.2');

        $this->assertSame('0.2000', $this->service()->activeRate());
        $this->assertSame('0.2000', $this->service()->calculate('50.00')->rate);
    }
}
