<?php

namespace Tests\Feature;

use App\Analytics\AdminAnalytics;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * Audit gap-fill for the dashboard analytics: the aggregation layer never
 * carries customer PII into a chart series, and the server-rendered chart
 * components honour RTL (line series reverses so newest sits at the leading
 * edge; bar fill anchors from the right). No production code changes.
 */
class AnalyticsAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_analytics_output_carries_no_customer_pii(): void
    {
        $category = ServiceCategory::factory()->create(['name' => 'Plumbing']);
        $customer = User::factory()->customer()->create([
            'name' => 'Zaphod Beeblebrox',
            'email' => 'zaphod-pii@example.test',
        ]);
        ServiceRequest::factory()->count(2)->status('pending')->create([
            'customer_id' => $customer->id,
            'service_category_id' => $category->id,
        ]);

        $analytics = new AdminAnalytics;
        $payloads = [
            'requestsPerDay' => $analytics->requestsPerDay(),
            'revenuePerDay' => $analytics->revenuePerDay(),
            'requestsByCategory' => $analytics->requestsByCategory(),
            'requestsByStatus' => $analytics->requestsByStatus(),
            'newCustomersPerDay' => $analytics->newCustomersPerDay(),
            'workerVerificationBreakdown' => $analytics->workerVerificationBreakdown(),
        ];

        foreach ($payloads as $name => $payload) {
            $json = json_encode($payload);
            $this->assertStringNotContainsString('Zaphod', $json, "$name leaked a customer name");
            $this->assertStringNotContainsString('Beeblebrox', $json, "$name leaked a customer name");
            $this->assertStringNotContainsString('zaphod-pii@example.test', $json, "$name leaked a customer email");
        }

        // Sanity: the customer's two pending requests really did land in the aggregates.
        $this->assertSame(2, collect($payloads['requestsByStatus'])
            ->firstWhere('label', __('status.request.pending'))['value']);
        $this->assertSame(2, collect($payloads['requestsByCategory'])
            ->firstWhere('label', 'Plumbing')['value']);
    }

    public function test_line_chart_reverses_the_series_in_arabic_so_newest_leads(): void
    {
        $series = [
            ['label' => 'D-one', 'value' => 1],
            ['label' => 'D-two', 'value' => 0],
            ['label' => 'D-three', 'value' => 5],
        ];

        app()->setLocale('en');
        $en = Blade::render('<x-chart-line :series="$series" title="t" />', compact('series'));
        $this->assertLessThan(strpos($en, 'D-three'), strpos($en, 'D-one'), 'LTR keeps oldest first');

        app()->setLocale('ar');
        $ar = Blade::render('<x-chart-line :series="$series" title="t" />', compact('series'));
        $this->assertLessThan(strpos($ar, 'D-one'), strpos($ar, 'D-three'), 'RTL puts newest first');
        app()->setLocale('en');
    }

    public function test_bar_chart_anchors_the_fill_from_the_right_in_arabic(): void
    {
        // value 2 of a max of 10 -> 20% bar. LTR draws it from x=0, RTL from x=80.
        $series = [
            ['label' => 'Big', 'value' => 10],
            ['label' => 'Small', 'value' => 2],
        ];

        app()->setLocale('en');
        $en = Blade::render('<x-chart-bars :series="$series" title="t" />', compact('series'));
        $this->assertStringNotContainsString('x="80"', $en);

        app()->setLocale('ar');
        $ar = Blade::render('<x-chart-bars :series="$series" title="t" />', compact('series'));
        $this->assertStringContainsString('x="80"', $ar);
        app()->setLocale('en');
    }

    public function test_admin_analytics_dashboard_renders_inside_the_rtl_shell_in_arabic(): void
    {
        $admin = User::factory()->admin()->create();
        ServiceCategory::factory()->create(['name' => 'Plumbing']);
        ServiceRequest::factory()->count(2)->create();

        $this->actingAs($admin)->withSession(['locale' => 'ar'])
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee('data-chart="admin-requests-by-status"', false)
            ->assertSeeText(__('dashboard.analytics_heading', [], 'ar'));
    }
}
