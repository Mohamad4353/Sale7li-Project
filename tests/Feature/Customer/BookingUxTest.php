<?php

namespace Tests\Feature\Customer;

use App\Models\Address;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * P10-12 — the booking page must explain the real SALE7LI workflow and must
 * not present a confusing empty required address select.
 */
class BookingUxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        ServiceCategory::factory()->create();
    }

    public function test_booking_page_explains_what_happens_next(): void
    {
        $customer = User::factory()->customer()->create();
        Address::factory()->for($customer, 'user')->create();

        $this->actingAs($customer)->get(route('customer.booking.create'))
            ->assertOk()
            ->assertSee(__('bookings.next_title'))
            ->assertSee(__('bookings.next_3'))   // "professional sends a quotation"
            ->assertSee(__('bookings.next_5'));  // "you pay"
    }

    public function test_what_happens_next_is_localized_in_arabic(): void
    {
        $customer = User::factory()->customer()->create();
        Address::factory()->for($customer, 'user')->create();

        $this->actingAs($customer)->withSession(['locale' => 'ar'])
            ->get(route('customer.booking.create'))
            ->assertOk()
            ->assertSee(__('bookings.next_title', [], 'ar'))
            ->assertDontSee(__('bookings.next_title', [], 'en'));
    }

    public function test_customer_with_no_address_sees_an_empty_state_and_a_cta_not_an_empty_select(): void
    {
        $customer = User::factory()->customer()->create();

        $response = $this->actingAs($customer)->get(route('customer.booking.create'))->assertOk();

        $response->assertSee(__('bookings.no_address_title'));
        $response->assertSee(__('bookings.add_first_address'));
        // The CTA now saves the in-progress form before leaving for address
        // management (Phase 27 draft), so it posts to the draft endpoint.
        $response->assertSee(route('customer.booking.draft'), false);
        $response->assertDontSee('name="address_id"', false);
    }

    public function test_customer_with_an_address_still_sees_the_address_select(): void
    {
        $customer = User::factory()->customer()->create();
        $address = Address::factory()->for($customer, 'user')->create(['title' => 'My Home Base']);

        $this->actingAs($customer)->get(route('customer.booking.create'))
            ->assertOk()
            ->assertSee('name="address_id"', false)
            ->assertSee('My Home Base');
    }

    public function test_a_preselected_category_is_confirmed_and_pre_checked(): void
    {
        $customer = User::factory()->customer()->create();
        $category = ServiceCategory::factory()->create(['name' => 'Gutter Cleaning', 'is_active' => true]);

        $this->actingAs($customer)
            ->get(route('customer.booking.create', ['category' => $category->slug]))
            ->assertOk()
            ->assertSee(__('bookings.preselected_note', ['name' => 'Gutter Cleaning']))
            ->assertSee('value="'.$category->id.'" checked', false);
    }

    public function test_an_unknown_preselect_category_is_ignored_and_shows_no_note(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->get(route('customer.booking.create', ['category' => 'not-a-real-slug']))
            ->assertOk()
            ->assertDontSee('Started from');
    }

    public function test_an_inactive_category_slug_is_not_preselected(): void
    {
        $customer = User::factory()->customer()->create();
        // A real slug, but the category is inactive — the create() lookup runs
        // against ServiceCategory::active(), so it must not pre-check or note it.
        $inactive = ServiceCategory::factory()->create(['name' => 'Retired Service', 'is_active' => false]);

        $this->actingAs($customer)
            ->get(route('customer.booking.create', ['category' => $inactive->slug]))
            ->assertOk()
            ->assertDontSee('Started from')
            ->assertDontSee('value="'.$inactive->id.'" checked', false)
            ->assertDontSee('Retired Service'); // inactive categories are never offered
    }

    public function test_the_preselected_note_is_localised_in_arabic(): void
    {
        $customer = User::factory()->customer()->create();
        $category = ServiceCategory::factory()->create(['name' => 'Gutter Cleaning', 'name_ar' => 'تنظيف المزاريب', 'is_active' => true]);

        $this->actingAs($customer)->withSession(['locale' => 'ar'])
            ->get(route('customer.booking.create', ['category' => $category->slug]))
            ->assertOk()
            ->assertSee(__('bookings.preselected_note', ['name' => 'تنظيف المزاريب'], 'ar'))
            ->assertDontSee('bookings.preselected_note');
    }
}
