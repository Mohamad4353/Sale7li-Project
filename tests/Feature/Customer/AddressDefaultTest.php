<?php

namespace Tests\Feature\Customer;

use App\Models\Address;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * P14-24-B — a one-click "Set as default" action for non-default addresses.
 * Ownership is enforced by AddressPolicy::update; no new migration, no
 * duplicate business logic (mirrors the existing default-swap in store/update).
 */
class AddressDefaultTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: Address, 2: Address} */
    private function twoAddresses(): array
    {
        $customer = User::factory()->customer()->create();
        $current = Address::factory()->create(['user_id' => $customer->id, 'is_default' => true]);
        $other = Address::factory()->create(['user_id' => $customer->id, 'is_default' => false]);

        return [$customer, $current, $other];
    }

    public function test_a_customer_can_promote_their_own_address_to_default(): void
    {
        [$customer, $current, $other] = $this->twoAddresses();

        $this->actingAs($customer)
            ->post(route('customer.addresses.default', $other))
            ->assertRedirect();

        $this->assertTrue($other->fresh()->is_default);
        $this->assertFalse($current->fresh()->is_default);
    }

    public function test_the_action_is_offered_only_for_non_default_addresses(): void
    {
        [$customer, $current, $other] = $this->twoAddresses();

        $this->actingAs($customer)->get(route('customer.addresses.index'))->assertOk()
            ->assertSee('action="'.route('customer.addresses.default', $other).'"', false)
            ->assertDontSee('action="'.route('customer.addresses.default', $current).'"', false)
            ->assertSee(__('addresses.set_default'));
    }

    public function test_a_customer_cannot_touch_another_customers_address(): void
    {
        [, , $other] = $this->twoAddresses();
        $intruder = User::factory()->customer()->create();

        $this->actingAs($intruder)
            ->post(route('customer.addresses.default', $other))
            ->assertForbidden();

        $this->assertFalse($other->fresh()->is_default);
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        [, , $other] = $this->twoAddresses();

        $this->post(route('customer.addresses.default', $other))->assertRedirect(route('login'));
    }
}
