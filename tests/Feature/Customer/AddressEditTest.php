<?php

namespace Tests\Feature\Customer;

use App\Models\Address;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * P10-25 — customers can edit their own addresses. Ownership is enforced; the
 * owner can never be reassigned and arbitrary address ids are rejected.
 */
class AddressEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_open_the_edit_form_prefilled(): void
    {
        $customer = User::factory()->customer()->create();
        $address = Address::factory()->for($customer, 'user')->create(['title' => 'Old Flat', 'city' => 'Beirut']);

        $this->actingAs($customer)->get(route('customer.addresses.edit', $address))
            ->assertOk()
            ->assertSee('Old Flat', false)
            ->assertSee('Beirut', false);
    }

    public function test_the_addresses_index_lists_only_the_customers_own_and_localises_to_arabic(): void
    {
        $mine = User::factory()->customer()->create();
        Address::factory()->for($mine, 'user')->create(['title' => 'My Home Base']);
        Address::factory()->for(User::factory()->customer()->create(), 'user')->create(['title' => 'Not Mine Villa']);

        $this->actingAs($mine)->get(route('customer.addresses.index'))
            ->assertOk()
            ->assertSee('My Home Base')
            ->assertDontSee('Not Mine Villa');

        $this->actingAs($mine)->withSession(['locale' => 'ar'])->get(route('customer.addresses.index'))
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee(__('addresses.title', [], 'ar'))
            ->assertDontSee('addresses.title')
            ->assertDontSee('addresses.add');
    }

    public function test_owner_can_update_their_address(): void
    {
        $customer = User::factory()->customer()->create();
        $address = Address::factory()->for($customer, 'user')->create(['title' => 'Old Flat']);

        $this->actingAs($customer)->put(route('customer.addresses.update', $address), [
            'title' => 'New Flat',
            'street' => '12 Maple Rd',
            'city' => 'Byblos',
            'area' => 'Old Town',
            'postal_code' => '4021',
            'instructions' => 'Blue gate',
        ])->assertRedirect(route('customer.addresses.index'));

        $this->assertDatabaseHas('addresses', [
            'id' => $address->id,
            'user_id' => $customer->id,
            'title' => 'New Flat',
            'city' => 'Byblos',
        ]);
    }

    public function test_a_customer_cannot_edit_or_update_a_foreign_address(): void
    {
        $owner = User::factory()->customer()->create();
        $address = Address::factory()->for($owner, 'user')->create(['title' => 'Owner Home']);
        $intruder = User::factory()->customer()->create();

        $this->actingAs($intruder)->get(route('customer.addresses.edit', $address))->assertForbidden();

        $this->actingAs($intruder)->put(route('customer.addresses.update', $address), [
            'title' => 'Hijacked', 'street' => 'x', 'city' => 'y',
        ])->assertForbidden();

        $this->assertDatabaseHas('addresses', ['id' => $address->id, 'title' => 'Owner Home']);
    }

    public function test_update_cannot_reassign_ownership(): void
    {
        $customer = User::factory()->customer()->create();
        $other = User::factory()->customer()->create();
        $address = Address::factory()->for($customer, 'user')->create();

        $this->actingAs($customer)->put(route('customer.addresses.update', $address), [
            'title' => 'Mine', 'street' => 's', 'city' => 'c',
            'user_id' => $other->id,
            'id' => 999999,
        ])->assertRedirect();

        $this->assertSame($customer->id, $address->fresh()->user_id);
    }

    public function test_update_validates_input(): void
    {
        $customer = User::factory()->customer()->create();
        $address = Address::factory()->for($customer, 'user')->create(['title' => 'Keep Me']);

        $this->actingAs($customer)->from(route('customer.addresses.edit', $address))
            ->put(route('customer.addresses.update', $address), ['title' => '', 'street' => '', 'city' => ''])
            ->assertSessionHasErrors(['title', 'street', 'city']);

        $this->assertDatabaseHas('addresses', ['id' => $address->id, 'title' => 'Keep Me']);
    }

    public function test_making_an_address_default_clears_the_previous_default(): void
    {
        $customer = User::factory()->customer()->create();
        $default = Address::factory()->for($customer, 'user')->create(['is_default' => true]);
        $other = Address::factory()->for($customer, 'user')->create(['is_default' => false]);

        $this->actingAs($customer)->put(route('customer.addresses.update', $other), [
            'title' => $other->title, 'street' => $other->street, 'city' => $other->city,
            'is_default' => '1',
        ])->assertRedirect();

        $this->assertFalse($default->fresh()->is_default);
        $this->assertTrue($other->fresh()->is_default);
    }

    public function test_a_worker_cannot_use_the_address_edit_routes(): void
    {
        $address = Address::factory()->create();

        $this->actingAs(User::factory()->worker()->create())
            ->get(route('customer.addresses.edit', $address))->assertForbidden();
    }

    public function test_delete_still_works(): void
    {
        $customer = User::factory()->customer()->create();
        $address = Address::factory()->for($customer, 'user')->create();

        $this->actingAs($customer)->delete(route('customer.addresses.destroy', $address))->assertRedirect();
        $this->assertModelMissing($address);
    }
}
