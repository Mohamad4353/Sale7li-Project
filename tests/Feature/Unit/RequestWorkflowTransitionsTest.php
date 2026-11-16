<?php

namespace Tests\Feature\Unit;

use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\WorkerProfile;
use App\Services\RequestWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

/**
 * Phase 28 — the request state machine's negative contract.
 *
 * Valid transitions are already exercised by the cancellation / reputation /
 * completion / invoice feature tests. This file pins the rejections: illegal
 * jumps, terminal-state finality, and the assign / accept / decline guard
 * clauses. Nothing here computes money.
 */
class RequestWorkflowTransitionsTest extends TestCase
{
    use RefreshDatabase;

    private function workflow(): RequestWorkflow
    {
        return app(RequestWorkflow::class);
    }

    private function request(string $status, ?User $worker = null): ServiceRequest
    {
        return ServiceRequest::factory()->status($status)->create(['worker_id' => $worker?->id]);
    }

    /** @return array<string, array{0: string, 1: list<string>}> */
    public static function stateTransitions(): array
    {
        return [
            'pending' => ['pending', ['accepted', 'rejected', 'cancelled']],
            'accepted' => ['accepted', ['scheduled', 'cancelled']],
            'scheduled' => ['scheduled', ['scheduled', 'on_the_way', 'cancelled']],
            'on_the_way' => ['on_the_way', ['in_progress', 'cancelled']],
            'in_progress' => ['in_progress', ['completed']],
            'completed' => ['completed', []],
            'cancelled' => ['cancelled', []],
            'rejected' => ['rejected', []],
        ];
    }

    /**
     * @dataProvider stateTransitions
     *
     * @param  list<string>  $expected
     */
    public function test_allowed_next_statuses_matches_the_state_machine(string $status, array $expected): void
    {
        $this->assertSame($expected, $this->workflow()->allowedNextStatuses($this->request($status)));
    }

    public function test_a_valid_transition_is_accepted_and_records_its_side_effects(): void
    {
        $worker = User::factory()->worker()->create();
        WorkerProfile::factory()->for($worker, 'user')->create();
        $request = $this->request('in_progress', $worker);

        $fresh = $this->workflow()->transition($request, 'completed');

        $this->assertSame('completed', $fresh->status);
        $this->assertNotNull($fresh->completed_at);
        $this->assertDatabaseHas('activity_logs', ['subject_id' => $request->id, 'action' => 'request.status_changed']);
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function illegalJumps(): array
    {
        return [
            'pending -> completed' => ['pending', 'completed'],
            'pending -> in_progress' => ['pending', 'in_progress'],
            'pending -> on_the_way' => ['pending', 'on_the_way'],
            'accepted -> completed' => ['accepted', 'completed'],
            'accepted -> on_the_way' => ['accepted', 'on_the_way'],
            'scheduled -> completed' => ['scheduled', 'completed'],
            'on_the_way -> completed' => ['on_the_way', 'completed'],
            'in_progress -> cancelled' => ['in_progress', 'cancelled'],
            'in_progress -> scheduled' => ['in_progress', 'scheduled'],
        ];
    }

    /**
     * @dataProvider illegalJumps
     */
    public function test_an_illegal_transition_throws_and_leaves_no_trace(string $from, string $to): void
    {
        $request = $this->request($from);

        $threw = false;
        try {
            $this->workflow()->transition($request, $to);
        } catch (LogicException $e) {
            $threw = true;
            $this->assertStringContainsString("{$from} -> {$to}", $e->getMessage());
        }

        $this->assertTrue($threw, "{$from} -> {$to} must be rejected");
        $this->assertSame($from, $request->fresh()->status);
        $this->assertDatabaseCount('notifications', 0);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    /** @return array<string, array{0: string}> */
    public static function terminalStates(): array
    {
        return ['completed' => ['completed'], 'cancelled' => ['cancelled'], 'rejected' => ['rejected']];
    }

    /**
     * @dataProvider terminalStates
     */
    public function test_a_terminal_state_rejects_every_transition(string $terminal): void
    {
        $request = $this->request($terminal);

        foreach (['pending', 'accepted', 'scheduled', 'on_the_way', 'in_progress', 'completed'] as $target) {
            $threw = false;
            try {
                $this->workflow()->transition($request, $target);
            } catch (LogicException $e) {
                $threw = true;
            }
            $this->assertTrue($threw, "{$terminal} -> {$target} must be rejected");
        }

        $this->assertSame($terminal, $request->fresh()->status);
    }

    public function test_worker_accept_rejects_a_worker_who_is_not_assigned(): void
    {
        $assigned = User::factory()->worker()->create();
        $intruder = User::factory()->worker()->create();
        $request = $this->request('pending', $assigned);

        $threw = false;
        try {
            $this->workflow()->workerAccept($request, $intruder);
        } catch (LogicException $e) {
            $threw = true;
        }

        $this->assertTrue($threw);
        $this->assertSame('pending', $request->fresh()->status);
    }

    public function test_worker_decline_requires_a_pending_request(): void
    {
        $worker = User::factory()->worker()->create();
        $request = $this->request('accepted', $worker);

        $threw = false;
        try {
            $this->workflow()->workerDecline($request, $worker);
        } catch (LogicException $e) {
            $threw = true;
        }

        $this->assertTrue($threw);
        $this->assertSame($worker->id, $request->fresh()->worker_id);
    }

    public function test_assign_requires_a_pending_request(): void
    {
        $request = $this->request('accepted');
        $worker = User::factory()->worker()->create();

        $threw = false;
        try {
            $this->workflow()->assign($request, $worker);
        } catch (LogicException $e) {
            $threw = true;
        }

        $this->assertTrue($threw);
        $this->assertNull($request->fresh()->worker_id);
    }

    public function test_worker_decline_returns_a_pending_request_to_the_unassigned_pool(): void
    {
        $worker = User::factory()->worker()->create();
        $request = $this->request('pending', $worker);

        $fresh = $this->workflow()->workerDecline($request, $worker);

        $this->assertNull($fresh->worker_id);
        $this->assertSame('pending', $fresh->status);
    }
}
