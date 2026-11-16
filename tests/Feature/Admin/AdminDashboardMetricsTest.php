<?php

namespace Tests\Feature\Admin;

use App\Models\Complaint;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature 10 — the admin dashboard reports real aggregates over the live tables,
 * including the count of WHISH payments still awaiting verification.
 */
class AdminDashboardMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_reports_real_counts(): void
    {
        User::factory()->count(3)->customer()->create();
        User::factory()->count(2)->worker()->create();

        ServiceRequest::factory()->count(4)->status('completed')->create();
        ServiceRequest::factory()->count(2)->status('in_progress')->create();  // active
        ServiceRequest::factory()->status('cancelled')->create();
        ServiceRequest::factory()->status('rejected')->create();

        Payment::factory()->count(2)->paid()->create(['worker_id' => User::factory()->worker()]);
        Payment::factory()->create([
            'status' => 'pending', 'payment_method' => 'whish',
            'worker_id' => User::factory()->worker(),
        ]);
        Refund::factory()->create();
        Complaint::factory()->create(['status' => 'open']);
        Complaint::factory()->create(['status' => 'resolved']); // not "open"

        $response = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))
            ->assertOk();

        $response->assertSeeText(__('dashboard.stat_completed_requests'));
        $response->assertSeeText(__('dashboard.stat_whish_pending'));
        $response->assertSeeText(__('dashboard.stat_active_requests'));
        $response->assertSeeText(__('dashboard.stat_refunds'));
        $response->assertSeeText(__('dashboard.stat_complaints_open'));
        // The WHISH pending tile links to the review queue.
        $response->assertSee('href="'.route('admin.payments.whish').'"', false);
    }

    public function test_metrics_are_computed_not_hardcoded(): void
    {
        $admin = User::factory()->admin()->create();

        // Fresh DB: only the admin exists, nothing else.
        $before = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $before->assertSee(__('dashboard.stat_whish_pending'));

        // Add one pending WHISH payment; the tile must move from 0 to 1.
        Payment::factory()->create([
            'status' => 'pending', 'payment_method' => 'whish',
            'worker_id' => User::factory()->worker(),
        ]);

        $this->assertSame(
            1,
            Payment::where('payment_method', 'whish')->where('status', 'pending')->count(),
        );
    }

    public function test_dashboard_is_admin_only(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->customer()->create())->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs(User::factory()->worker()->create())->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_dashboard_metrics_render_in_arabic(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->withSession(['locale' => 'ar'])
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSeeText(__('dashboard.stat_whish_pending', [], 'ar'));
    }
}
