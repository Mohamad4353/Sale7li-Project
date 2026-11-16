<?php

namespace Tests\Feature;

use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\WorkerProfile;
use App\Services\RequestWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkerReputationTest extends TestCase
{
    use RefreshDatabase;

    public function test_completing_jobs_recomputes_completed_count_and_rates_from_real_data(): void
    {
        $worker = User::factory()->worker()->create();
        $profile = WorkerProfile::factory()->for($worker, 'user')->verified()->create([
            'completed_jobs' => 126,
            'completion_rate' => 98,
            'cancellation_rate' => 2,
        ]);
        $workflow = app(RequestWorkflow::class);

        foreach (range(1, 3) as $i) {
            $request = ServiceRequest::factory()->status('in_progress')->create(['worker_id' => $worker->id]);
            $workflow->transition($request, 'completed');
        }

        $cancelled = ServiceRequest::factory()->status('accepted')->create(['worker_id' => $worker->id]);
        $workflow->transition($cancelled, 'cancelled');

        $profile->refresh();
        $this->assertSame(3, $profile->completed_jobs);
        $this->assertEqualsWithDelta(75.0, (float) $profile->completion_rate, 0.01);
        $this->assertEqualsWithDelta(25.0, (float) $profile->cancellation_rate, 0.01);
    }

    public function test_new_worker_with_no_finished_jobs_keeps_neutral_rates(): void
    {
        $worker = User::factory()->worker()->create();
        $profile = WorkerProfile::factory()->for($worker, 'user')->verified()->create([
            'completed_jobs' => 126,
            'completion_rate' => 98,
            'cancellation_rate' => 2,
        ]);
        $workflow = app(RequestWorkflow::class);

        $request = ServiceRequest::factory()->status('in_progress')->create(['worker_id' => $worker->id]);
        $workflow->transition($request, 'completed');

        $profile->refresh();
        $this->assertSame(1, $profile->completed_jobs);
        $this->assertEqualsWithDelta(100.0, (float) $profile->completion_rate, 0.01);
        $this->assertEqualsWithDelta(0.0, (float) $profile->cancellation_rate, 0.01);
    }

    public function test_review_updates_worker_rating_average(): void
    {
        $customer = User::factory()->customer()->create();
        $worker = User::factory()->worker()->create();
        $profile = WorkerProfile::factory()->for($worker, 'user')->verified()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('completed')->create([
            'worker_id' => $worker->id,
        ]);

        $this->actingAs($customer)
            ->post(route('customer.reviews.store', $request), ['rating' => 4, 'comment' => 'Solid work.'])
            ->assertRedirect();

        $profile->refresh();
        $this->assertSame(1, $profile->rating_count);
        $this->assertEqualsWithDelta(4.0, (float) $profile->rating_average, 0.01);
        $this->assertDatabaseHas('reviews', ['service_request_id' => $request->id, 'rating' => 4]);
    }
}
