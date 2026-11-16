<?php

namespace Tests\Feature\Admin;

use App\Models\Payment;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 29 — the admin dashboard renders the analytics section (KPI cards + the
 * three server-rendered SVG charts) inside the existing admin-only route, and
 * the pre-existing dashboard behaviour is untouched.
 */
class AdminAnalyticsRenderTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function seedSomeActivity(): void
    {
        $category = ServiceCategory::factory()->create(['name' => 'Plumbing']);
        ServiceRequest::factory()->count(3)->create(['service_category_id' => $category->id]);
        ServiceRequest::factory()->status('completed')->create(['service_category_id' => $category->id]);
        Payment::factory()->paid()->create(['amount' => 120]);
    }

    public function test_admin_dashboard_shows_the_analytics_section(): void
    {
        $this->seedSomeActivity();

        $response = $this->actingAs($this->admin())->get(route('admin.dashboard'))->assertOk();

        $response->assertSeeText(__('dashboard.analytics_heading'));
        $response->assertSeeText(__('dashboard.stat_verified_workers'));
        $response->assertSeeText(__('dashboard.stat_completion_rate'));
        $response->assertSeeText(__('dashboard.stat_cancellation_rate'));
        $response->assertSeeText(__('dashboard.stat_avg_requests_day'));
        $response->assertSeeText(__('dashboard.chart_requests_30d'));
        $response->assertSeeText(__('dashboard.chart_revenue_30d'));
        $response->assertSeeText(__('dashboard.chart_by_category'));

        // The SVG charts actually drew.
        $response->assertSee('<polyline', false);
        $response->assertSee('<rect', false);
        // No chart library — everything is inline SVG.
        $response->assertDontSee('cdn.jsdelivr', false);
        $response->assertDontSee('chart.js', false);
    }

    public function test_charts_show_an_empty_state_on_a_fresh_database(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.dashboard'))->assertOk();

        $response->assertSeeText(__('dashboard.chart_no_data'));
        $response->assertDontSee('<polyline', false);
    }

    public function test_analytics_renders_in_arabic_with_no_raw_keys(): void
    {
        $this->seedSomeActivity();

        $response = $this->actingAs($this->admin())->withSession(['locale' => 'ar'])
            ->get(route('admin.dashboard'))->assertOk();

        $response->assertSeeText(__('dashboard.analytics_heading', [], 'ar'));
        $response->assertSeeText(__('dashboard.chart_by_category', [], 'ar'));
        $response->assertDontSee('dashboard.chart');
        $response->assertDontSee('dashboard.stat_verified');
    }

    public function test_existing_dashboard_stats_are_untouched(): void
    {
        $this->seedSomeActivity();
        Payment::factory()->create(['status' => 'pending', 'payment_method' => 'whish']);

        $response = $this->actingAs($this->admin())->get(route('admin.dashboard'))->assertOk();

        $response->assertSeeText(__('dashboard.stat_users'));
        $response->assertSeeText(__('dashboard.stat_revenue'));
        $response->assertSeeText(__('dashboard.stat_completed_requests'));
        $response->assertSeeText(__('dashboard.stat_whish_pending'));
        $response->assertSee('href="'.route('admin.payments.whish').'"', false);
    }

    public function test_dashboard_with_analytics_is_still_admin_only(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->customer()->create())->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs(User::factory()->worker()->create())->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_dashboard_renders_the_six_admin_charts_with_stable_selectors(): void
    {
        $this->seedSomeActivity();

        $response = $this->actingAs($this->admin())->get(route('admin.dashboard'))->assertOk();

        foreach ([
            'admin-requests-30d', 'admin-revenue-30d', 'admin-new-customers-30d',
            'admin-requests-by-status', 'admin-worker-verification', 'admin-requests-by-category',
        ] as $selector) {
            $response->assertSee('data-chart="'.$selector.'"', false);
        }

        $response->assertSeeText(__('dashboard.chart_requests_by_status'));
        $response->assertSeeText(__('dashboard.chart_new_customers_30d'));
        $response->assertSeeText(__('dashboard.chart_worker_verification'));
    }

    public function test_new_admin_charts_render_in_arabic_with_no_raw_keys(): void
    {
        $this->seedSomeActivity();

        $response = $this->actingAs($this->admin())->withSession(['locale' => 'ar'])
            ->get(route('admin.dashboard'))->assertOk();

        $response->assertSeeText(__('dashboard.chart_requests_by_status', [], 'ar'));
        $response->assertDontSee('dashboard.chart');
        $response->assertDontSee('status.request.');
        $response->assertDontSee('status.verification.');
    }
}
