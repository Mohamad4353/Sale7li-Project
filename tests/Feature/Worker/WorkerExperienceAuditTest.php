<?php

namespace Tests\Feature\Worker;

use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fills the last thin spots in the worker-journey regression net: dashboard
 * recent-jobs isolation, and Arabic/RTL rendering of the earnings and portfolio
 * management pages (previously only verified by hand). No production changes.
 */
class WorkerExperienceAuditTest extends TestCase
{
    use InteractsWithWorkers, RefreshDatabase;

    public function test_the_dashboard_recent_jobs_are_scoped_to_the_signed_in_worker(): void
    {
        $category = ServiceCategory::factory()->create();

        $mine = $this->workerUser(['name' => 'Mine Worker']);
        $mineJob = ServiceRequest::factory()->status('accepted')->create([
            'worker_id' => $mine->id, 'service_category_id' => $category->id,
        ]);

        $other = $this->workerUser(['name' => 'Other Worker']);
        $otherJob = ServiceRequest::factory()->status('accepted')->create([
            'worker_id' => $other->id, 'service_category_id' => $category->id,
        ]);

        $this->actingAs($mine)->get(route('worker.dashboard'))
            ->assertOk()
            ->assertSee($mineJob->reference)
            ->assertDontSee($otherJob->reference);
    }

    public function test_the_earnings_page_renders_in_arabic_without_raw_keys(): void
    {
        $worker = $this->workerUser();

        $this->actingAs($worker)->withSession(['locale' => 'ar'])
            ->get(route('worker.earnings'))
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee(__('payments.earnings_title', [], 'ar'))
            ->assertSee(__('payments.gross', [], 'ar'))
            ->assertDontSee('payments.earnings')
            ->assertDontSee('dashboard.worker_portal');
    }

    public function test_the_portfolio_management_page_renders_in_arabic_without_raw_keys(): void
    {
        $worker = $this->workerUser();

        $this->actingAs($worker)->withSession(['locale' => 'ar'])
            ->get(route('worker.portfolio.index'))
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee(__('workers.portfolio_title', [], 'ar'))
            ->assertSee(__('workers.portfolio_none_title', [], 'ar'))   // empty state, localized
            ->assertDontSee('workers.portfolio_');
    }

    public function test_a_customer_cannot_reach_any_worker_management_page(): void
    {
        $customer = User::factory()->customer()->create();

        foreach ([
            'worker.dashboard', 'worker.jobs.index', 'worker.services.edit',
            'worker.availability.edit', 'worker.portfolio.index', 'worker.earnings',
            'worker.profile.edit', 'worker.complaints.index',
        ] as $name) {
            $this->actingAs($customer)->get(route($name))->assertForbidden();
        }
    }
}
