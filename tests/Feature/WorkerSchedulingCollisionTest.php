<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\WorkerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ATTACK POINT #3: the same worker must never be able to hold two active
 * appointments at the identical date/time. This is an application-level
 * guard only — BookingAvailability (advisory, category-wide) and
 * WorkerAvailability (recurring weekly template) are untouched and are not
 * the collision source; the source of truth here is Appointment rows.
 */
class WorkerSchedulingCollisionTest extends TestCase
{
    use RefreshDatabase;

    private function assignedRequest(User $worker, array $attrs = []): ServiceRequest
    {
        return ServiceRequest::factory()
            ->for(User::factory()->customer()->create(), 'customer')
            ->status('accepted')
            ->create(array_merge([
                'service_category_id' => ServiceCategory::factory()->create()->id,
                'worker_id' => $worker->id,
            ], $attrs));
    }

    private function schedule(User $worker, ServiceRequest $request, string $date, string $time)
    {
        return $this->actingAs($worker)->post(route('worker.jobs.status', $request), [
            'status' => 'scheduled',
            'scheduled_date' => $date,
            'scheduled_time' => $time,
        ]);
    }

    public function test_worker_cannot_schedule_a_second_request_at_the_same_date_and_time(): void
    {
        $worker = User::factory()->worker()->create();
        WorkerProfile::factory()->for($worker, 'user')->verified()->create();
        $date = now()->addDays(3)->toDateString();

        $requestA = $this->assignedRequest($worker);
        $this->schedule($worker, $requestA, $date, '10:00')->assertRedirect();

        $requestB = $this->assignedRequest($worker);

        $this->from(route('worker.jobs.show', $requestB))
            ->schedule($worker, $requestB, $date, '10:00')
            ->assertSessionHasErrors('scheduled_time');
    }

    public function test_the_rejected_request_keeps_its_previous_state_and_gets_no_appointment(): void
    {
        $worker = User::factory()->worker()->create();
        WorkerProfile::factory()->for($worker, 'user')->verified()->create();
        $date = now()->addDays(3)->toDateString();

        $requestA = $this->assignedRequest($worker);
        $this->schedule($worker, $requestA, $date, '10:00');

        $requestB = $this->assignedRequest($worker);
        $this->schedule($worker, $requestB, $date, '10:00');

        $this->assertSame('accepted', $requestB->fresh()->status);
        $this->assertNull($requestB->fresh()->appointment);
        $this->assertSame(1, Appointment::count());
    }

    public function test_same_worker_can_schedule_the_same_date_at_a_different_time(): void
    {
        $worker = User::factory()->worker()->create();
        WorkerProfile::factory()->for($worker, 'user')->verified()->create();
        $date = now()->addDays(3)->toDateString();

        $requestA = $this->assignedRequest($worker);
        $this->schedule($worker, $requestA, $date, '10:00')->assertRedirect();

        $requestB = $this->assignedRequest($worker);
        $this->schedule($worker, $requestB, $date, '11:00')->assertRedirect();

        $this->assertSame('scheduled', $requestB->fresh()->status);
    }

    public function test_same_worker_can_schedule_the_same_time_on_a_different_date(): void
    {
        $worker = User::factory()->worker()->create();
        WorkerProfile::factory()->for($worker, 'user')->verified()->create();

        $requestA = $this->assignedRequest($worker);
        $this->schedule($worker, $requestA, now()->addDays(3)->toDateString(), '10:00')->assertRedirect();

        $requestB = $this->assignedRequest($worker);
        $this->schedule($worker, $requestB, now()->addDays(4)->toDateString(), '10:00')->assertRedirect();

        $this->assertSame('scheduled', $requestB->fresh()->status);
    }

    public function test_two_different_workers_can_share_the_same_date_and_time(): void
    {
        $workerOne = User::factory()->worker()->create();
        WorkerProfile::factory()->for($workerOne, 'user')->verified()->create();
        $workerTwo = User::factory()->worker()->create();
        WorkerProfile::factory()->for($workerTwo, 'user')->verified()->create();
        $date = now()->addDays(3)->toDateString();

        $requestA = $this->assignedRequest($workerOne);
        $this->schedule($workerOne, $requestA, $date, '10:00')->assertRedirect();

        $requestB = $this->assignedRequest($workerTwo);
        $this->schedule($workerTwo, $requestB, $date, '10:00')->assertRedirect();

        $this->assertSame('scheduled', $requestB->fresh()->status);
    }

    public function test_a_worker_can_reschedule_their_own_request_onto_its_own_existing_slot(): void
    {
        $worker = User::factory()->worker()->create();
        WorkerProfile::factory()->for($worker, 'user')->verified()->create();
        $date = now()->addDays(3)->toDateString();

        $request = $this->assignedRequest($worker);
        $this->schedule($worker, $request, $date, '10:00')->assertRedirect();

        // Re-submitting the exact same slot for the exact same request must
        // not be treated as a collision against itself.
        $this->schedule($worker, $request, $date, '10:00')->assertRedirect();

        $this->assertSame('scheduled', $request->fresh()->status);
        $this->assertSame(1, Appointment::count());
    }

    public function test_a_cancelled_appointment_does_not_block_the_same_slot_for_another_request(): void
    {
        $worker = User::factory()->worker()->create();
        WorkerProfile::factory()->for($worker, 'user')->verified()->create();
        $date = now()->addDays(3)->toDateString();

        $requestA = $this->assignedRequest($worker);
        $this->schedule($worker, $requestA, $date, '10:00');
        Appointment::where('service_request_id', $requestA->id)->update(['status' => 'cancelled']);

        $requestB = $this->assignedRequest($worker);
        $this->schedule($worker, $requestB, $date, '10:00')->assertRedirect();

        $this->assertSame('scheduled', $requestB->fresh()->status);
    }

    public function test_a_completed_appointment_does_not_block_the_same_slot_for_another_request(): void
    {
        $worker = User::factory()->worker()->create();
        WorkerProfile::factory()->for($worker, 'user')->verified()->create();
        $date = now()->addDays(3)->toDateString();

        $requestA = $this->assignedRequest($worker);
        $this->schedule($worker, $requestA, $date, '10:00');
        Appointment::where('service_request_id', $requestA->id)->update(['status' => 'completed']);

        $requestB = $this->assignedRequest($worker);
        $this->schedule($worker, $requestB, $date, '10:00')->assertRedirect();

        $this->assertSame('scheduled', $requestB->fresh()->status);
    }
}
