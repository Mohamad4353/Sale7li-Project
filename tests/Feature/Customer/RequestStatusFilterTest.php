<?php

namespace Tests\Feature\Customer;

use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * P14-08 — server-side status filter pills on the customer request list.
 * Grouping maps onto the existing RequestWorkflow states; no new states.
 */
class RequestStatusFilterTest extends TestCase
{
    use RefreshDatabase;

    private function requestFor(User $customer, string $status): ServiceRequest
    {
        return ServiceRequest::factory()->for($customer, 'customer')->status($status)->create([
            'service_category_id' => ServiceCategory::factory()->create()->id,
        ]);
    }

    public function test_default_list_shows_every_status_and_only_the_customers_own(): void
    {
        $customer = User::factory()->customer()->create();
        $mine = collect(['pending', 'in_progress', 'completed', 'cancelled'])
            ->mapWithKeys(fn ($s) => [$s => $this->requestFor($customer, $s)->reference]);
        $other = $this->requestFor(User::factory()->customer()->create(), 'completed');

        $response = $this->actingAs($customer)->get(route('customer.requests.index'));

        $response->assertOk();
        foreach ($mine as $reference) {
            $response->assertSee($reference);
        }
        $response->assertDontSee($other->reference);
    }

    public function test_active_filter_limits_to_in_flight_requests(): void
    {
        $customer = User::factory()->customer()->create();
        $active = $this->requestFor($customer, 'on_the_way')->reference;
        $done = $this->requestFor($customer, 'completed')->reference;
        $gone = $this->requestFor($customer, 'cancelled')->reference;

        $this->actingAs($customer)->get(route('customer.requests.index', ['status' => 'active']))
            ->assertOk()
            ->assertSee($active)
            ->assertDontSee($done)
            ->assertDontSee($gone);
    }

    public function test_completed_filter_shows_only_completed(): void
    {
        $customer = User::factory()->customer()->create();
        $done = $this->requestFor($customer, 'completed')->reference;
        $active = $this->requestFor($customer, 'accepted')->reference;

        $this->actingAs($customer)->get(route('customer.requests.index', ['status' => 'completed']))
            ->assertOk()
            ->assertSee($done)
            ->assertDontSee($active);
    }

    public function test_cancelled_filter_covers_cancelled_and_rejected(): void
    {
        $customer = User::factory()->customer()->create();
        $cancelled = $this->requestFor($customer, 'cancelled')->reference;
        $rejected = $this->requestFor($customer, 'rejected')->reference;
        $active = $this->requestFor($customer, 'pending')->reference;

        $this->actingAs($customer)->get(route('customer.requests.index', ['status' => 'cancelled']))
            ->assertOk()
            ->assertSee($cancelled)
            ->assertSee($rejected)
            ->assertDontSee($active);
    }

    public function test_filter_never_leaks_another_customers_records(): void
    {
        $customer = User::factory()->customer()->create();
        $intruderRow = $this->requestFor(User::factory()->customer()->create(), 'completed');

        $this->actingAs($customer)->get(route('customer.requests.index', ['status' => 'completed']))
            ->assertOk()
            ->assertDontSee($intruderRow->reference);
    }

    public function test_unknown_status_value_falls_back_to_the_default_list(): void
    {
        $customer = User::factory()->customer()->create();
        $done = $this->requestFor($customer, 'completed')->reference;
        $active = $this->requestFor($customer, 'pending')->reference;

        $this->actingAs($customer)->get(route('customer.requests.index', ['status' => 'bogus']))
            ->assertOk()
            ->assertSee($done)
            ->assertSee($active);
    }

    public function test_pagination_preserves_the_status_parameter(): void
    {
        $customer = User::factory()->customer()->create();
        for ($i = 0; $i < 12; $i++) {
            $this->requestFor($customer, 'completed');
        }

        $this->actingAs($customer)->get(route('customer.requests.index', ['status' => 'completed']))
            ->assertOk()
            ->assertSee('status=completed', false)
            ->assertSee('page=2', false);
    }
}
