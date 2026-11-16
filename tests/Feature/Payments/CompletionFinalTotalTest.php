<?php

namespace Tests\Feature\Payments;

use App\Models\Quotation;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\RequestWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompletionFinalTotalTest extends TestCase
{
    use BuildsPaidJobs, RefreshDatabase;

    private function workflow(): RequestWorkflow
    {
        return app(RequestWorkflow::class);
    }

    public function test_the_completion_transition_snapshots_final_total_from_the_accepted_amount(): void
    {
        $worker = User::factory()->worker()->create();
        $request = ServiceRequest::factory()->status('in_progress')->create(['worker_id' => $worker->id]);
        Quotation::factory()->create([
            'service_request_id' => $request->id,
            'worker_id' => $worker->id,
            'total' => 275,
            'status' => 'accepted',
        ]);
        $request->update(['quoted_total' => 275]); // as QuotationController@accept would

        $this->assertNull($request->fresh()->final_total);

        $this->workflow()->transition($request->fresh(), 'completed');

        $this->assertSame('275.00', $request->fresh()->final_total);
        $this->assertSame($request->fresh()->quoted_total, $request->fresh()->final_total);
    }

    public function test_final_total_is_not_overwritten_once_frozen(): void
    {
        $worker = User::factory()->worker()->create();
        $request = ServiceRequest::factory()->status('in_progress')->create([
            'worker_id' => $worker->id,
            'quoted_total' => 200,
            'final_total' => 150, // already frozen at a different value
        ]);

        $this->workflow()->transition($request->fresh(), 'completed');

        $this->assertSame('150.00', $request->fresh()->final_total);
    }

    public function test_completion_without_a_quotation_does_not_fabricate_final_total(): void
    {
        $worker = User::factory()->worker()->create();
        $request = ServiceRequest::factory()->status('in_progress')->create(['worker_id' => $worker->id]);

        $this->workflow()->transition($request->fresh(), 'completed');

        $this->assertNull($request->fresh()->final_total);
        $this->assertSame('completed', $request->fresh()->status);
    }

    public function test_the_job_controller_no_longer_writes_final_total_itself(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/Worker/JobController.php'));
        $this->assertStringNotContainsString("'final_total'", $source, 'JobController must not write final_total directly');
    }

    public function test_final_total_is_frozen_when_a_job_completes_through_the_worker_status_endpoint(): void
    {
        ['request' => $request] = $this->quotedCompletedJob(['base_price' => 90, 'tax' => 10]);

        $this->assertSame('100.00', $request->final_total);
        $this->assertSame('completed', $request->status);
    }

    public function test_a_later_transition_cannot_mutate_final_total_because_completed_is_terminal(): void
    {
        ['request' => $request] = $this->quotedCompletedJob(['base_price' => 100]);
        $this->assertSame('100.00', $request->final_total);

        $this->expectException(\LogicException::class);
        $this->workflow()->transition($request->fresh(), 'cancelled');
    }

    public function test_state_transition_security_is_unaffected_worker_cannot_skip_to_completed(): void
    {
        $worker = User::factory()->worker()->create();
        $request = ServiceRequest::factory()->status('accepted')->create(['worker_id' => $worker->id]);

        $this->from(route('worker.jobs.show', $request))
            ->actingAs($worker)
            ->post(route('worker.jobs.status', $request), ['status' => 'completed'])
            ->assertSessionHasErrors();

        $this->assertSame('accepted', $request->fresh()->status);
        $this->assertNull($request->fresh()->final_total);
    }
}
