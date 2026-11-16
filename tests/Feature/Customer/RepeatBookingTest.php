<?php

namespace Tests\Feature\Customer;

use App\Models\Address;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature 8 — "Book again". Opens the normal booking form pre-filled from a past
 * request; submitting still runs through store() and creates a fresh request.
 * No payment record is ever copied or duplicated.
 */
class RepeatBookingTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{customer: User, request: ServiceRequest, category: ServiceCategory, address: Address} */
    private function completedRequest(): array
    {
        $customer = User::factory()->customer()->create();
        $category = ServiceCategory::factory()->create(['name' => 'Appliance Repair']);
        $address = Address::factory()->for($customer, 'user')->create(['title' => 'My Flat']);
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('completed')->create([
            'service_category_id' => $category->id,
            'address_id' => $address->id,
            'problem_description' => 'The washing machine leaks during the spin cycle.',
            'is_emergency' => true,
        ]);

        return compact('customer', 'request', 'category', 'address');
    }

    public function test_rebook_prefills_the_form_from_the_past_request(): void
    {
        ['customer' => $customer, 'request' => $request, 'category' => $category, 'address' => $address] = $this->completedRequest();

        $response = $this->actingAs($customer)->get(route('customer.requests.rebook', $request))->assertOk();

        $response->assertSee('The washing machine leaks during the spin cycle.');
        $response->assertSee('value="'.$category->id.'" checked', false);
        $response->assertSee('value="'.$address->id.'" selected', false);
        $response->assertSee('name="is_emergency" value="1" checked', false);
    }

    public function test_rebook_is_limited_to_the_owning_customer(): void
    {
        ['request' => $request] = $this->completedRequest();

        $this->get(route('customer.requests.rebook', $request))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->customer()->create())->get(route('customer.requests.rebook', $request))->assertForbidden();
        $this->actingAs(User::factory()->worker()->create())->get(route('customer.requests.rebook', $request))->assertForbidden();
    }

    public function test_submitting_after_rebook_creates_a_new_request_and_no_payment(): void
    {
        ['customer' => $customer, 'request' => $original, 'category' => $category, 'address' => $address] = $this->completedRequest();

        $this->actingAs($customer)->get(route('customer.requests.rebook', $original))->assertOk();

        $this->actingAs($customer)->post(route('customer.booking.store'), [
            'service_category_id' => $category->id,
            'address_id' => $address->id,
            'problem_description' => 'Same machine, the fault came back.',
            'preferred_date' => now()->addDay()->toDateString(),
            'preferred_time' => '09:00',
            'is_emergency' => '1',
        ])->assertRedirect();

        $this->assertSame(2, ServiceRequest::where('customer_id', $customer->id)->count());
        $this->assertSame('completed', $original->fresh()->status);      // untouched
        $this->assertNotSame($original->reference, ServiceRequest::latest('id')->first()->reference);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_book_again_button_appears_only_on_a_completed_request(): void
    {
        ['customer' => $customer, 'request' => $completed] = $this->completedRequest();
        $pending = ServiceRequest::factory()->for($customer, 'customer')->status('pending')->create();

        $this->actingAs($customer)->get(route('customer.requests.show', $completed))
            ->assertOk()->assertSee(__('requests.book_again'));

        $this->actingAs($customer)->get(route('customer.requests.show', $pending))
            ->assertOk()->assertDontSee(__('requests.book_again'));
    }

    public function test_rebook_form_renders_in_arabic(): void
    {
        ['customer' => $customer, 'request' => $request] = $this->completedRequest();

        $this->actingAs($customer)->withSession(['locale' => 'ar'])
            ->get(route('customer.requests.rebook', $request))
            ->assertOk()
            ->assertSee(__('bookings.title', [], 'ar'));
    }
}
