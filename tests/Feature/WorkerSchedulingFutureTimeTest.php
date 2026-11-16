<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\WorkerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * ATTACK POINT #4 / F1: `UpdateJobStatusRequest` validated `scheduled_date`
 * as `after_or_equal:today` in isolation, so "today at a time that already
 * passed" slipped through. This mirrors the combined-instant check that
 * StoreBookingRequest and StoreRescheduleRequestRequest already use.
 */
class WorkerSchedulingFutureTimeTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function assignedRequest(User $worker): ServiceRequest
    {
        return ServiceRequest::factory()
            ->for(User::factory()->customer()->create(), 'customer')
            ->status('accepted')
            ->create([
                'service_category_id' => ServiceCategory::factory()->create()->id,
                'worker_id' => $worker->id,
            ]);
    }

    private function schedule(User $worker, ServiceRequest $request, string $date, string $time)
    {
        return $this->actingAs($worker)->post(route('worker.jobs.status', $request), [
            'status' => 'scheduled',
            'scheduled_date' => $date,
            'scheduled_time' => $time,
        ]);
    }

    public function test_worker_cannot_schedule_today_at_a_time_already_in_the_past(): void
    {
        Carbon::setTestNow('2026-06-15 14:00:00');
        $worker = User::factory()->worker()->create();
        WorkerProfile::factory()->for($worker, 'user')->verified()->create();
        $request = $this->assignedRequest($worker);

        $this->from(route('worker.jobs.show', $request))
            ->schedule($worker, $request, '2026-06-15', '09:00')
            ->assertSessionHasErrors('scheduled_time');
    }

    public function test_worker_can_schedule_today_at_a_future_time(): void
    {
        Carbon::setTestNow('2026-06-15 08:00:00');
        $worker = User::factory()->worker()->create();
        WorkerProfile::factory()->for($worker, 'user')->verified()->create();
        $request = $this->assignedRequest($worker);

        $this->schedule($worker, $request, '2026-06-15', '14:00')->assertRedirect();

        $this->assertSame('scheduled', $request->fresh()->status);
    }

    public function test_worker_can_schedule_a_future_date_normally(): void
    {
        Carbon::setTestNow('2026-06-15 14:00:00');
        $worker = User::factory()->worker()->create();
        WorkerProfile::factory()->for($worker, 'user')->verified()->create();
        $request = $this->assignedRequest($worker);

        $this->schedule($worker, $request, '2026-06-16', '09:00')->assertRedirect();

        $this->assertSame('scheduled', $request->fresh()->status);
    }

    public function test_existing_valid_scheduling_behavior_is_unchanged(): void
    {
        Carbon::setTestNow('2026-06-15 08:00:00');
        $worker = User::factory()->worker()->create();
        WorkerProfile::factory()->for($worker, 'user')->verified()->create();
        $request = $this->assignedRequest($worker);

        $this->schedule($worker, $request, '2026-06-20', '10:30')->assertRedirect();

        $appointment = $request->fresh()->appointment;
        $this->assertNotNull($appointment);
        $this->assertSame('2026-06-20', $appointment->scheduled_date->toDateString());
        $this->assertSame('10:30', $appointment->scheduled_time);
    }

    public function test_a_rejected_past_time_scheduling_attempt_creates_no_appointment(): void
    {
        Carbon::setTestNow('2026-06-15 14:00:00');
        $worker = User::factory()->worker()->create();
        WorkerProfile::factory()->for($worker, 'user')->verified()->create();
        $request = $this->assignedRequest($worker);

        $this->schedule($worker, $request, '2026-06-15', '09:00');

        $this->assertNull($request->fresh()->appointment);
        $this->assertSame(0, Appointment::count());
    }

    public function test_the_request_status_is_unchanged_after_rejection(): void
    {
        Carbon::setTestNow('2026-06-15 14:00:00');
        $worker = User::factory()->worker()->create();
        WorkerProfile::factory()->for($worker, 'user')->verified()->create();
        $request = $this->assignedRequest($worker);

        $this->schedule($worker, $request, '2026-06-15', '09:00');

        $this->assertSame('accepted', $request->fresh()->status);
    }
}
