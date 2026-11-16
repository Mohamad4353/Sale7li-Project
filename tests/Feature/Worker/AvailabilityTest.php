<?php

namespace Tests\Feature\Worker;

use App\Models\User;
use App\Models\WorkerAvailability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvailabilityTest extends TestCase
{
    use InteractsWithWorkers, RefreshDatabase;

    public function test_a_worker_can_save_their_weekly_availability(): void
    {
        $worker = $this->workerUser();

        $this->actingAs($worker)
            ->put(route('worker.availability.update'), $this->weekPayload([0, 2, 4], '08:30', '16:00'))
            ->assertRedirect();

        $monday = WorkerAvailability::where(['worker_id' => $worker->id, 'day_of_week' => 0])->firstOrFail();
        $this->assertTrue((bool) $monday->is_available);
        $this->assertSame('08:30', substr((string) $monday->start_time, 0, 5));
        $this->assertSame('16:00', substr((string) $monday->end_time, 0, 5));

        $this->assertDatabaseHas('worker_availability', [
            'worker_id' => $worker->id, 'day_of_week' => 1, 'is_available' => false, 'start_time' => null, 'end_time' => null,
        ]);
        $this->assertSame(7, WorkerAvailability::where('worker_id', $worker->id)->count());
    }

    public function test_a_day_key_outside_zero_to_six_is_rejected(): void
    {
        $worker = $this->workerUser();

        $payload = $this->weekPayload([1]);
        $payload['days'][7] = ['is_available' => '1', 'start' => '09:00', 'end' => '17:00'];

        $this->actingAs($worker)->from(route('worker.availability.edit'))
            ->put(route('worker.availability.update'), $payload)
            ->assertSessionHasErrors('days');

        $this->assertDatabaseCount('worker_availability', 0);
    }

    public function test_an_invalid_time_format_is_rejected(): void
    {
        $worker = $this->workerUser();

        $payload = $this->weekPayload([2]);
        $payload['days'][2] = ['is_available' => '1', 'start' => '9am', 'end' => '17:00'];

        $this->actingAs($worker)->from(route('worker.availability.edit'))
            ->put(route('worker.availability.update'), $payload)
            ->assertSessionHasErrors('days.2.start');

        $this->assertDatabaseCount('worker_availability', 0);
    }

    public function test_an_end_time_before_or_equal_to_the_start_is_rejected(): void
    {
        $worker = $this->workerUser();

        $this->actingAs($worker)->from(route('worker.availability.edit'))
            ->put(route('worker.availability.update'), $this->weekPayload([3], '17:00', '09:00'))
            ->assertSessionHasErrors('days.3.end');

        $this->actingAs($worker)->from(route('worker.availability.edit'))
            ->put(route('worker.availability.update'), $this->weekPayload([3], '12:00', '12:00'))
            ->assertSessionHasErrors('days.3.end');

        $this->assertDatabaseCount('worker_availability', 0);
    }

    public function test_an_enabled_day_without_times_is_rejected(): void
    {
        $worker = $this->workerUser();

        $payload = $this->weekPayload([1]);
        $payload['days'][1] = ['is_available' => '1'];

        $this->actingAs($worker)->from(route('worker.availability.edit'))
            ->put(route('worker.availability.update'), $payload)
            ->assertSessionHasErrors('days.1.end');
    }

    public function test_disabling_a_day_clears_its_stored_times(): void
    {
        $worker = $this->workerUser();
        $this->availabilityRow($worker, 4, ['start_time' => '10:00', 'end_time' => '18:00']);

        $this->actingAs($worker)
            ->put(route('worker.availability.update'), $this->weekPayload([]))
            ->assertRedirect();

        $this->assertDatabaseHas('worker_availability', [
            'worker_id' => $worker->id, 'day_of_week' => 4, 'is_available' => false, 'start_time' => null, 'end_time' => null,
        ]);
    }

    public function test_a_worker_cannot_write_availability_for_another_worker(): void
    {
        $owner = $this->workerUser();
        $intruder = $this->workerUser();

        $payload = $this->weekPayload([0]);
        $payload['worker_id'] = $owner->id;   // ignored — worker_id always comes from auth

        $this->actingAs($intruder)->put(route('worker.availability.update'), $payload)->assertRedirect();

        $this->assertDatabaseMissing('worker_availability', ['worker_id' => $owner->id]);
        $this->assertDatabaseHas('worker_availability', ['worker_id' => $intruder->id, 'day_of_week' => 0, 'is_available' => true]);
    }

    public function test_a_customer_cannot_reach_availability_management(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)->get(route('worker.availability.edit'))->assertForbidden();
        $this->actingAs($customer)->put(route('worker.availability.update'), $this->weekPayload([1]))->assertForbidden();
        $this->assertDatabaseCount('worker_availability', 0);
    }
}
