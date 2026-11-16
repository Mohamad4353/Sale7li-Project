<?php

namespace Tests\Feature\Customer;

use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 29 — the customer dashboard renders its two charts, scoped to the
 * signed-in customer only, inside the existing customer-only route.
 */
class CustomerDashboardChartsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_both_customer_charts(): void
    {
        $customer = User::factory()->customer()->create();
        ServiceRequest::factory()->count(3)->create(['customer_id' => $customer->id]);

        $response = $this->actingAs($customer)->get(route('customer.dashboard'))->assertOk();

        $response->assertSeeText(__('dashboard.chart_my_requests_status'));
        $response->assertSeeText(__('dashboard.chart_my_requests_30d'));
        $response->assertSee('data-chart="customer-requests-by-status"', false);
        $response->assertSee('data-chart="customer-requests-30d"', false);
    }

    public function test_charts_only_reflect_the_signed_in_customers_data(): void
    {
        $mine = User::factory()->customer()->create();
        $other = User::factory()->customer()->create();

        // The other customer has recent activity; I have none.
        ServiceRequest::factory()->count(5)->create(['customer_id' => $other->id]);

        $response = $this->actingAs($mine)->get(route('customer.dashboard'))->assertOk();

        // My "requests · last 30 days" line chart has no data of its own.
        $response->assertSee('data-chart="customer-requests-30d"', false);
        $response->assertSeeText(__('dashboard.chart_no_data'));
    }

    public function test_customer_charts_render_in_arabic_without_raw_keys(): void
    {
        $customer = User::factory()->customer()->create();
        ServiceRequest::factory()->create(['customer_id' => $customer->id]);

        $response = $this->actingAs($customer)->withSession(['locale' => 'ar'])
            ->get(route('customer.dashboard'))->assertOk();

        $response->assertSeeText(__('dashboard.chart_my_requests_status', [], 'ar'));
        $response->assertDontSee('dashboard.chart');
        $response->assertDontSee('status.request.');
    }

    public function test_customer_dashboard_stays_customer_only(): void
    {
        $this->get(route('customer.dashboard'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->worker()->create())->get(route('customer.dashboard'))->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get(route('customer.dashboard'))->assertForbidden();
    }
}
