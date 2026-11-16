<?php

namespace Tests\Feature\Worker;

use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Emergency prioritisation, worker side. Display + ordering only — no state
 * transition, no auto-assignment, no matching logic.
 */
class WorkerEmergencyJobsTest extends TestCase
{
    use RefreshDatabase;

    private function assignedJob(User $worker, array $attrs): ServiceRequest
    {
        return ServiceRequest::factory()->status('accepted')->create([
            'worker_id' => $worker->id,
            ...$attrs,
        ]);
    }

    public function test_emergency_jobs_are_marked_and_listed_first(): void
    {
        $worker = User::factory()->worker()->create();
        $normal = $this->assignedJob($worker, ['reference' => 'SL-NORMAL', 'is_emergency' => false, 'created_at' => now()]);
        $urgent = $this->assignedJob($worker, ['reference' => 'SL-URGENT', 'is_emergency' => true, 'created_at' => now()->subDay()]);

        $response = $this->actingAs($worker)->get(route('worker.jobs.index'))->assertOk();

        $response->assertSee(__('workers.emergency'));
        // Emergency sorts ahead even though it is older than the normal job.
        $response->assertSeeInOrder(['SL-URGENT', 'SL-NORMAL']);
    }

    public function test_a_list_without_emergencies_has_no_emergency_marker(): void
    {
        $worker = User::factory()->worker()->create();
        $this->assignedJob($worker, ['is_emergency' => false]);

        $this->actingAs($worker)->get(route('worker.jobs.index'))
            ->assertOk()
            ->assertDontSee(__('workers.emergency'));
    }

    public function test_viewing_the_list_never_changes_job_state(): void
    {
        $worker = User::factory()->worker()->create();
        $urgent = $this->assignedJob($worker, ['is_emergency' => true, 'status' => 'accepted']);

        $this->actingAs($worker)->get(route('worker.jobs.index'))->assertOk();

        $this->assertSame('accepted', $urgent->fresh()->status);
        $this->assertTrue($urgent->fresh()->is_emergency);
    }

    public function test_emergency_label_is_localized(): void
    {
        $worker = User::factory()->worker()->create();
        $this->assignedJob($worker, ['is_emergency' => true]);

        $this->actingAs($worker)->withSession(['locale' => 'ar'])
            ->get(route('worker.jobs.index'))
            ->assertOk()
            ->assertSee(__('workers.emergency', [], 'ar'))
            ->assertDontSee(__('workers.emergency', [], 'en'));
    }
}
