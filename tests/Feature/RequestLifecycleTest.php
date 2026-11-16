<?php

namespace Tests\Feature;

use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\WorkerProfile;
use App\Services\RequestWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression coverage for two request-lifecycle details that the broader
 * workflow/security suite exercises implicitly but never asserts on directly:
 * the SL- reference-number contract, and the fact that the scheduling
 * transition owns its Appointment server-side (worker_id is the acting worker,
 * never a client value) and re-flags it on a reschedule.
 *
 * RequestWorkflow is frozen — nothing here changes it.
 */
class RequestLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function newRequest(RequestWorkflow $workflow, User $customer, ServiceCategory $category): ServiceRequest
    {
        return $workflow->create([
            'customer_id' => $customer->id,
            'service_category_id' => $category->id,
            'problem_description' => 'x',
            'preferred_date' => now()->addDay()->toDateString(),
            'preferred_time' => '10:00',
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);
    }

    public function test_every_request_gets_a_unique_sl_reference(): void
    {
        $workflow = app(RequestWorkflow::class);
        $customer = User::factory()->customer()->create();
        $category = ServiceCategory::factory()->create();

        $refs = [];
        for ($i = 0; $i < 8; $i++) {
            $ref = $this->newRequest($workflow, $customer, $category)->reference;
            $this->assertMatchesRegularExpression('/^SL-[A-Z0-9]{8}$/', $ref, "reference [$ref] is off-contract");
            $refs[] = $ref;
        }

        $this->assertCount(8, array_unique($refs), 'references must be unique');
    }

    public function test_a_client_supplied_reference_is_replaced_by_the_server(): void
    {
        $workflow = app(RequestWorkflow::class);
        $customer = User::factory()->customer()->create();

        $request = $workflow->create([
            'customer_id' => $customer->id,
            'service_category_id' => ServiceCategory::factory()->create()->id,
            'problem_description' => 'x',
            'preferred_date' => now()->addDay()->toDateString(),
            'preferred_time' => '10:00',
            'status' => 'pending',
            'payment_status' => 'pending',
            'reference' => 'SL-FORGED1', // caller value must be overwritten
        ]);

        $this->assertNotSame('SL-FORGED1', $request->reference);
        $this->assertMatchesRegularExpression('/^SL-[A-Z0-9]{8}$/', $request->reference);
    }

    public function test_scheduling_owns_its_appointment_server_side_and_a_reschedule_reflags_it(): void
    {
        $worker = User::factory()->worker()->create();
        WorkerProfile::factory()->for($worker, 'user')->verified()->create();
        $otherWorker = User::factory()->worker()->create();

        $request = ServiceRequest::factory()
            ->for(User::factory()->customer()->create(), 'customer')
            ->status('accepted')
            ->create([
                'service_category_id' => ServiceCategory::factory()->create()->id,
                'worker_id' => $worker->id,
            ]);

        // First schedule — with an injected worker_id that must be ignored.
        $this->actingAs($worker)->post(route('worker.jobs.status', $request), [
            'status' => 'scheduled',
            'scheduled_date' => now()->addDays(2)->toDateString(),
            'scheduled_time' => '09:30',
            'worker_id' => $otherWorker->id,
        ])->assertRedirect();

        $request->refresh();
        $this->assertSame('scheduled', $request->status);
        $appointment = $request->appointment()->sole();
        $this->assertSame($worker->id, $appointment->worker_id);      // the acting worker, not the injected one
        $this->assertSame('scheduled', $appointment->status);

        // Re-schedule — same request, still `scheduled` (self-loop) — flags the appointment.
        $this->actingAs($worker)->post(route('worker.jobs.status', $request), [
            'status' => 'scheduled',
            'scheduled_date' => now()->addDays(4)->toDateString(),
            'scheduled_time' => '14:00',
        ])->assertRedirect();

        $appointment->refresh();
        $this->assertSame('rescheduled', $appointment->status);
        $this->assertSame($worker->id, $appointment->worker_id);
        $this->assertSame(1, $request->appointment()->count(), 'a reschedule updates, it does not duplicate');
    }

    public function test_a_non_assigned_worker_cannot_schedule_the_job(): void
    {
        $worker = User::factory()->worker()->create();
        WorkerProfile::factory()->for($worker, 'user')->verified()->create();

        $request = ServiceRequest::factory()
            ->for(User::factory()->customer()->create(), 'customer')
            ->status('accepted')
            ->create([
                'service_category_id' => ServiceCategory::factory()->create()->id,
                'worker_id' => $worker->id,
            ]);

        $intruder = User::factory()->worker()->create();
        WorkerProfile::factory()->for($intruder, 'user')->verified()->create();

        $this->actingAs($intruder)->post(route('worker.jobs.status', $request), [
            'status' => 'scheduled',
            'scheduled_date' => now()->addDays(2)->toDateString(),
            'scheduled_time' => '09:30',
        ])->assertForbidden();

        $this->assertSame('accepted', $request->fresh()->status);
        $this->assertNull($request->fresh()->appointment);
    }
}
