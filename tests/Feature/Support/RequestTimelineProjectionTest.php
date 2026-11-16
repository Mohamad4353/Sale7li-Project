<?php

namespace Tests\Feature\Support;

use App\Models\ActivityLog;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\WorkerProfile;
use App\Models\WorkerService;
use App\Services\RequestWorkflow;
use App\Support\RequestTimeline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Contract for App\Support\RequestTimeline — the read-only projection that
 * rebuilds a request's lifecycle from the ActivityLog rows RequestWorkflow
 * already writes. It must return the ACTUAL recorded timestamps and never
 * invent an entry for a transition that has not happened.
 */
class RequestTimelineProjectionTest extends TestCase
{
    use RefreshDatabase;

    private function driven(string $stopAt): ServiceRequest
    {
        $workflow = app(RequestWorkflow::class);
        $customer = User::factory()->customer()->create();
        $category = ServiceCategory::factory()->create();
        $worker = User::factory()->worker()->create();
        WorkerProfile::factory()->for($worker, 'user')->verified()->create();
        WorkerService::create(['worker_id' => $worker->id, 'service_category_id' => $category->id, 'price_from' => 40, 'is_active' => true]);

        $request = $workflow->create([
            'customer_id' => $customer->id, 'service_category_id' => $category->id,
            'problem_description' => 'x', 'preferred_date' => now()->addDay()->toDateString(),
            'preferred_time' => '10:00', 'status' => 'pending', 'payment_status' => 'pending',
        ]);

        foreach (['assigned', 'accepted', 'scheduled', 'on_the_way', 'in_progress', 'completed'] as $step) {
            if ($step === 'assigned') {
                $workflow->assign($request, $worker);
            } elseif ($step === 'accepted') {
                $request = $workflow->workerAccept($request->fresh(), $worker);
            } else {
                $request = $workflow->transition($request->fresh(), $step);
            }
            if ($step === $stopAt) {
                break;
            }
        }

        return $request->fresh();
    }

    public function test_it_returns_the_actual_recorded_activity_log_timestamps(): void
    {
        $request = $this->driven('in_progress');

        $rows = ActivityLog::where('subject_type', ServiceRequest::class)
            ->where('subject_id', $request->id)->orderBy('id')->get();

        $expected = [
            'submitted' => $rows->firstWhere('action', 'request.created')->created_at,
            'assigned' => $rows->firstWhere('action', 'request.assigned')->created_at,
        ];
        foreach ($rows->where('action', 'request.status_changed') as $log) {
            $expected[$log->metadata['status']] ??= $log->created_at;
        }

        $map = RequestTimeline::for($request);

        foreach ($expected as $key => $carbon) {
            $this->assertArrayHasKey($key, $map, "timeline is missing [$key]");
            $this->assertTrue($carbon->equalTo($map[$key]), "[$key] is not the recorded ActivityLog time");
        }
        // Nothing invented for a transition that never happened.
        $this->assertArrayNotHasKey('completed', $map);
    }

    public function test_a_request_with_no_history_falls_back_to_created_at_only(): void
    {
        // A factory row created outside the workflow — no ActivityLog at all.
        $request = ServiceRequest::factory()->status('in_progress')->create();

        $map = RequestTimeline::for($request);

        $this->assertSame(['submitted'], array_keys($map));
        $this->assertTrue($request->created_at->equalTo($map['submitted']));
    }

    public function test_the_completed_at_column_is_the_authoritative_completion_time(): void
    {
        $request = $this->driven('completed');

        $map = RequestTimeline::for($request);

        $this->assertArrayHasKey('completed', $map);
        $this->assertTrue($request->completed_at->equalTo($map['completed']));
    }

    public function test_the_first_time_a_state_is_entered_wins_over_a_later_re_entry(): void
    {
        $request = $this->driven('scheduled');
        $firstScheduled = RequestTimeline::for($request)['scheduled'];

        // A reschedule is a scheduled -> scheduled self-loop; it writes a second
        // request.status_changed row. The projection must keep the FIRST one.
        app(RequestWorkflow::class)->transition($request->fresh(), 'scheduled');

        $this->assertTrue($firstScheduled->equalTo(RequestTimeline::for($request->fresh())['scheduled']));
    }
}
