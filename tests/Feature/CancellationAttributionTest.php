<?php

namespace Tests\Feature;

use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\WorkerProfile;
use App\Services\RequestWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancellationAttributionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{worker: User, profile: WorkerProfile}
     */
    private function workerWithTwoCompletedJobs(): array
    {
        $worker = User::factory()->worker()->create();
        $profile = WorkerProfile::factory()->for($worker, 'user')->verified()->create([
            'completed_jobs' => 0,
            'completion_rate' => 100,
            'cancellation_rate' => 0,
        ]);
        $workflow = app(RequestWorkflow::class);

        foreach (range(1, 2) as $i) {
            $job = ServiceRequest::factory()->status('in_progress')->create(['worker_id' => $worker->id]);
            $workflow->transition($job, 'completed');
        }
        $profile->refresh();

        return ['worker' => $worker, 'profile' => $profile];
    }

    public function test_customer_cancellation_is_attributed_to_the_customer(): void
    {
        $customer = User::factory()->customer()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('pending')->create();

        $this->actingAs($customer)
            ->post(route('customer.requests.cancel', $request))
            ->assertRedirect();

        $request->refresh();
        $this->assertSame('cancelled', $request->status);
        $this->assertSame('customer', $request->cancelled_by);
    }

    public function test_customer_cancellation_does_not_move_worker_reputation(): void
    {
        ['worker' => $worker, 'profile' => $profile] = $this->workerWithTwoCompletedJobs();
        $customer = User::factory()->customer()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('accepted')->create([
            'worker_id' => $worker->id,
        ]);

        $this->actingAs($customer)
            ->post(route('customer.requests.cancel', $request))
            ->assertRedirect();

        $profile->refresh();
        $this->assertSame(2, $profile->completed_jobs);
        $this->assertEqualsWithDelta(100.0, (float) $profile->completion_rate, 0.01);
        $this->assertEqualsWithDelta(0.0, (float) $profile->cancellation_rate, 0.01);
    }

    public function test_worker_attributed_cancellation_still_affects_reputation(): void
    {
        ['worker' => $worker, 'profile' => $profile] = $this->workerWithTwoCompletedJobs();
        $request = ServiceRequest::factory()->status('accepted')->create(['worker_id' => $worker->id]);

        app(RequestWorkflow::class)->transition($request, 'cancelled', 'worker');

        $profile->refresh();
        $this->assertSame(2, $profile->completed_jobs);
        $this->assertEqualsWithDelta(66.67, (float) $profile->completion_rate, 0.05);
        $this->assertEqualsWithDelta(33.33, (float) $profile->cancellation_rate, 0.05);
    }

    public function test_admin_attributed_cancellation_does_not_penalise_the_worker(): void
    {
        ['worker' => $worker, 'profile' => $profile] = $this->workerWithTwoCompletedJobs();
        $request = ServiceRequest::factory()->status('accepted')->create(['worker_id' => $worker->id]);

        app(RequestWorkflow::class)->transition($request, 'cancelled', 'admin');

        $profile->refresh();
        $this->assertEqualsWithDelta(100.0, (float) $profile->completion_rate, 0.01);
        $this->assertEqualsWithDelta(0.0, (float) $profile->cancellation_rate, 0.01);
    }

    public function test_legacy_cancellation_without_attribution_still_counts_and_does_not_crash(): void
    {
        ['worker' => $worker, 'profile' => $profile] = $this->workerWithTwoCompletedJobs();

        // A row cancelled before attribution existed.
        ServiceRequest::factory()->status('cancelled')->create([
            'worker_id' => $worker->id,
            'cancelled_by' => null,
        ]);

        // Trigger a recompute through the normal path.
        $job = ServiceRequest::factory()->status('in_progress')->create(['worker_id' => $worker->id]);
        app(RequestWorkflow::class)->transition($job, 'completed');

        $profile->refresh();
        $this->assertSame(3, $profile->completed_jobs);
        // 3 completed / (3 completed + 1 legacy-cancelled) = 75%.
        $this->assertEqualsWithDelta(75.0, (float) $profile->completion_rate, 0.05);
        $this->assertEqualsWithDelta(25.0, (float) $profile->cancellation_rate, 0.05);
    }

    public function test_cancelled_by_cannot_be_forged_through_request_input(): void
    {
        $customer = User::factory()->customer()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('pending')->create();

        $this->actingAs($customer)->post(route('customer.requests.cancel', $request), [
            'cancelled_by' => 'worker',
        ])->assertRedirect();

        $this->assertSame('customer', $request->fresh()->cancelled_by);
    }

    public function test_cancelled_by_is_not_mass_assignable(): void
    {
        $request = ServiceRequest::factory()->status('pending')->create();

        $request->fill(['cancelled_by' => 'worker', 'cancellation_reason' => 'x']);
        $this->assertNull($request->cancelled_by);

        $request->update(['status' => 'cancelled', 'cancelled_by' => 'worker']);
        $this->assertNull($request->fresh()->cancelled_by);
    }

    public function test_customer_can_record_an_optional_cancellation_reason(): void
    {
        $customer = User::factory()->customer()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('pending')->create();

        $this->actingAs($customer)->post(route('customer.requests.cancel', $request), [
            'reason' => 'schedule_changed',
        ])->assertRedirect();

        $this->assertSame('schedule_changed', $request->fresh()->cancellation_reason);
    }

    public function test_an_unknown_cancellation_reason_is_rejected(): void
    {
        $customer = User::factory()->customer()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('pending')->create();

        $this->from(route('customer.requests.show', $request))
            ->actingAs($customer)
            ->post(route('customer.requests.cancel', $request), ['reason' => 'not-a-real-reason'])
            ->assertSessionHasErrors('reason');

        $this->assertSame('pending', $request->fresh()->status);
    }
}
