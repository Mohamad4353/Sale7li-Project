<?php

namespace Tests\Feature\Marketplace;

use App\Models\Address;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceRequestFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_detail_links_request_cta_with_the_category(): void
    {
        $customer = User::factory()->customer()->create();
        $service = ServiceCategory::factory()->create(['slug' => 'ac-hvac', 'is_active' => true]);

        $this->actingAs($customer)->get(route('services.show', $service))
            ->assertOk()
            ->assertSee(route('customer.booking.create', ['category' => 'ac-hvac']), false);
    }

    public function test_booking_form_preselects_the_category_from_the_query_string(): void
    {
        $customer = User::factory()->customer()->create();
        $service = ServiceCategory::factory()->create(['slug' => 'painting', 'is_active' => true]);

        $response = $this->actingAs($customer)->get(route('customer.booking.create', ['category' => 'painting']));

        $response->assertOk();
        $response->assertSee('value="'.$service->id.'" checked', false);
    }

    public function test_an_unknown_category_slug_preselects_nothing(): void
    {
        $customer = User::factory()->customer()->create();
        ServiceCategory::factory()->create(['slug' => 'real-one', 'is_active' => true]);

        $this->actingAs($customer)->get(route('customer.booking.create', ['category' => 'not-a-real-slug']))
            ->assertOk()
            ->assertDontSee('checked="checked"', false);
    }

    public function test_the_existing_booking_submission_still_works(): void
    {
        $customer = User::factory()->customer()->create();
        $service = ServiceCategory::factory()->create(['is_active' => true]);
        $address = Address::factory()->for($customer, 'user')->create();

        $this->actingAs($customer)->post(route('customer.booking.store'), [
            'service_category_id' => $service->id,
            'problem_description' => 'Coming from the marketplace.',
            'address_id' => $address->id,
            'preferred_date' => now()->addDay()->toDateString(),
            'preferred_time' => '10:00',
        ])->assertRedirect();

        $this->assertDatabaseHas('service_requests', [
            'customer_id' => $customer->id,
            'service_category_id' => $service->id,
            'status' => 'pending',
        ]);
    }

    public function test_booking_still_rejects_a_foreign_address(): void
    {
        $customer = User::factory()->customer()->create();
        $someoneElse = User::factory()->customer()->create();
        $service = ServiceCategory::factory()->create(['is_active' => true]);
        $foreignAddress = Address::factory()->for($someoneElse, 'user')->create();

        $this->actingAs($customer)->from(route('customer.booking.create'))->post(route('customer.booking.store'), [
            'service_category_id' => $service->id,
            'problem_description' => 'Trying a foreign address.',
            'address_id' => $foreignAddress->id,
            'preferred_date' => now()->addDay()->toDateString(),
            'preferred_time' => '10:00',
        ])->assertSessionHasErrors('address_id');

        $this->assertSame(0, ServiceRequest::count());
    }
}
