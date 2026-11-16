<?php

namespace Tests\Feature\Worker;

use App\Models\Payment;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The worker dashboard "Earnings" card must show the worker's net at capture
 * (from frozen Payment snapshots), not the gross `final_total` of completed
 * jobs — which ignores commission and counts completed jobs the customer never
 * paid for.
 */
class WorkerDashboardEarningsTest extends TestCase
{
    use InteractsWithWorkers, RefreshDatabase;

    public function test_dashboard_earnings_is_worker_net_at_capture_not_gross_of_completed_jobs(): void
    {
        $worker = $this->workerUser();

        // A paid job: gross 100, commission 10, net 90.
        Payment::factory()->paid()->create([
            'worker_id' => $worker->id,
            'amount' => 100, 'commission_rate' => '0.1000', 'commission_amount' => 10, 'worker_net' => 90,
        ]);

        // A completed job the customer has NOT paid for (final_total frozen at 500).
        ServiceRequest::factory()
            ->for(User::factory()->customer()->create(), 'customer')
            ->create([
                'service_category_id' => ServiceCategory::factory()->create()->id,
                'worker_id' => $worker->id,
                'status' => 'completed',
                'quoted_total' => '500.00',
                'final_total' => '500.00',
                'payment_status' => 'pending',
            ]);

        $response = $this->actingAs($worker)->get(route('worker.dashboard'))->assertOk();

        $response->assertSee('$90');       // net at capture
        $response->assertDontSee('$600');  // gross of completed (100 + 500)
        $response->assertDontSee('$590');  // gross paid + unpaid
        $response->assertDontSee('$500');  // the unpaid completed job
    }

    public function test_dashboard_earnings_matches_the_earnings_page_headline(): void
    {
        $worker = $this->workerUser();

        Payment::factory()->paid()->create(['worker_id' => $worker->id, 'amount' => 250, 'commission_amount' => 25, 'worker_net' => 225]);
        Payment::factory()->paid()->create(['worker_id' => $worker->id, 'amount' => 100, 'commission_amount' => 10, 'worker_net' => 90]);
        // a pending payment for the same worker must not count
        Payment::factory()->create(['worker_id' => $worker->id, 'status' => 'pending', 'amount' => 9999, 'worker_net' => 9000]);

        $this->actingAs($worker)->get(route('worker.dashboard'))
            ->assertOk()
            ->assertSee('$315');       // 225 + 90

        $this->actingAs($worker)->get(route('worker.earnings'))
            ->assertOk()
            ->assertSee('$315.00');    // same figure, "Worker net at capture" on the earnings page
    }

    public function test_a_worker_with_no_paid_jobs_sees_zero_earnings_on_the_dashboard(): void
    {
        $worker = $this->workerUser();

        ServiceRequest::factory()
            ->for(User::factory()->customer()->create(), 'customer')
            ->create([
                'service_category_id' => ServiceCategory::factory()->create()->id,
                'worker_id' => $worker->id, 'status' => 'completed',
                'quoted_total' => '800.00', 'final_total' => '800.00', 'payment_status' => 'pending',
            ]);

        $this->actingAs($worker)->get(route('worker.dashboard'))
            ->assertOk()
            ->assertSee('$0')
            ->assertDontSee('$800');
    }
}
