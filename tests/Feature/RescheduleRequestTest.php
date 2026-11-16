<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\Quotation;
use App\Models\RescheduleRequest;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Customer-requested appointment rescheduling. Deliberately NOT a new
 * workflow — ServiceRequest.status is never touched by the create/accept/
 * decline/supersede paths; acceptance reuses the existing, already-permitted
 * `scheduled -> scheduled` self-transition (RequestWorkflow), exactly the way
 * the worker's own unilateral reschedule already does.
 */
class RescheduleRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** @return array{customer: User, worker: User, request: ServiceRequest, appointment: Appointment} */
    private function scheduledJob(array $requestAttrs = []): array
    {
        $customer = User::factory()->customer()->create();
        $worker = User::factory()->worker()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('scheduled')->create(array_merge([
            'worker_id' => $worker->id,
        ], $requestAttrs));
        $appointment = Appointment::create([
            'service_request_id' => $request->id,
            'worker_id' => $worker->id,
            'scheduled_date' => now()->addDays(2)->toDateString(),
            'scheduled_time' => '10:00',
            'status' => 'scheduled',
        ]);

        return compact('customer', 'worker', 'request', 'appointment');
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'proposed_date' => now()->addDays(5)->toDateString(),
            'proposed_time' => '15:00',
            'reason' => 'Something came up.',
        ], $overrides);
    }

    /* ------------------------------ creation ------------------------------ */

    public function test_customer_can_request_reschedule_for_own_scheduled_request(): void
    {
        ['customer' => $customer, 'request' => $request] = $this->scheduledJob();

        $this->actingAs($customer)
            ->post(route('customer.requests.reschedule', $request), $this->validPayload())
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('reschedule_requests', [
            'service_request_id' => $request->id,
            'status' => 'pending',
            'proposed_time' => '15:00',
        ]);
        $this->assertSame('scheduled', $request->fresh()->status);
    }

    public function test_customer_cannot_request_reschedule_for_another_customers_request(): void
    {
        ['request' => $request] = $this->scheduledJob();
        $stranger = User::factory()->customer()->create();

        $this->actingAs($stranger)
            ->post(route('customer.requests.reschedule', $request), $this->validPayload())
            ->assertForbidden();

        $this->assertDatabaseCount('reschedule_requests', 0);
    }

    /** @dataProvider ineligibleStatuses */
    public function test_customer_cannot_request_reschedule_when_not_scheduled(string $status): void
    {
        $customer = User::factory()->customer()->create();
        $worker = User::factory()->worker()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status($status)->create([
            'worker_id' => $status === 'pending' ? null : $worker->id,
        ]);

        $this->actingAs($customer)
            ->from(route('customer.requests.show', $request))
            ->post(route('customer.requests.reschedule', $request), $this->validPayload())
            ->assertSessionHasErrors('status');

        $this->assertDatabaseCount('reschedule_requests', 0);
    }

    public static function ineligibleStatuses(): array
    {
        return [
            ['pending'], ['accepted'], ['on_the_way'], ['in_progress'],
            ['completed'], ['cancelled'], ['rejected'],
        ];
    }

    public function test_a_guest_is_sent_to_login(): void
    {
        ['request' => $request] = $this->scheduledJob();

        $this->post(route('customer.requests.reschedule', $request), $this->validPayload())
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('reschedule_requests', 0);
    }

    public function test_a_worker_cannot_use_the_customer_route(): void
    {
        ['request' => $request] = $this->scheduledJob();

        $this->actingAs(User::factory()->worker()->create())
            ->post(route('customer.requests.reschedule', $request), $this->validPayload())
            ->assertForbidden();
    }

    /* ---------------------------- validation ----------------------------- */

    public function test_a_past_date_is_rejected(): void
    {
        ['customer' => $customer, 'request' => $request] = $this->scheduledJob();

        $this->actingAs($customer)
            ->from(route('customer.requests.show', $request))
            ->post(route('customer.requests.reschedule', $request), $this->validPayload([
                'proposed_date' => now()->subDay()->toDateString(),
            ]))
            ->assertSessionHasErrors('proposed_date');

        $this->assertDatabaseCount('reschedule_requests', 0);
    }

    public function test_a_date_more_than_90_days_ahead_is_rejected(): void
    {
        ['customer' => $customer, 'request' => $request] = $this->scheduledJob();

        $this->actingAs($customer)
            ->from(route('customer.requests.show', $request))
            ->post(route('customer.requests.reschedule', $request), $this->validPayload([
                'proposed_date' => now()->addDays(91)->toDateString(),
            ]))
            ->assertSessionHasErrors('proposed_date');

        $this->assertDatabaseCount('reschedule_requests', 0);
    }

    public function test_an_invalid_time_is_rejected(): void
    {
        ['customer' => $customer, 'request' => $request] = $this->scheduledJob();

        $this->actingAs($customer)
            ->from(route('customer.requests.show', $request))
            ->post(route('customer.requests.reschedule', $request), $this->validPayload([
                'proposed_time' => 'not-a-time',
            ]))
            ->assertSessionHasErrors('proposed_time');

        $this->assertDatabaseCount('reschedule_requests', 0);
    }

    public function test_only_one_pending_proposal_is_allowed_at_a_time(): void
    {
        ['customer' => $customer, 'request' => $request] = $this->scheduledJob();

        $this->actingAs($customer)->post(route('customer.requests.reschedule', $request), $this->validPayload())
            ->assertRedirect();

        $this->actingAs($customer)
            ->from(route('customer.requests.show', $request))
            ->post(route('customer.requests.reschedule', $request), $this->validPayload())
            ->assertSessionHasErrors('status');

        $this->assertSame(1, RescheduleRequest::where('service_request_id', $request->id)->count());
    }

    /* ------------------------------- worker view --------------------------- */

    public function test_the_assigned_worker_sees_the_pending_proposal(): void
    {
        ['customer' => $customer, 'worker' => $worker, 'request' => $request] = $this->scheduledJob();
        $this->actingAs($customer)->post(route('customer.requests.reschedule', $request), $this->validPayload());

        $this->actingAs($worker)->get(route('worker.jobs.show', $request))
            ->assertOk()
            ->assertSee(__('workers.reschedule_pending_title'))
            ->assertSee('Something came up.');
    }

    public function test_another_worker_cannot_accept_or_decline_it(): void
    {
        ['customer' => $customer, 'request' => $request] = $this->scheduledJob();
        $this->actingAs($customer)->post(route('customer.requests.reschedule', $request), $this->validPayload());
        $proposal = RescheduleRequest::sole();
        $intruder = User::factory()->worker()->create();

        $this->actingAs($intruder)->post(route('worker.reschedule.accept', $proposal))->assertForbidden();
        $this->actingAs($intruder)->post(route('worker.reschedule.decline', $proposal))->assertForbidden();

        $this->assertSame('pending', $proposal->fresh()->status);
    }

    public function test_the_customer_cannot_use_the_worker_response_endpoints(): void
    {
        ['customer' => $customer, 'request' => $request] = $this->scheduledJob();
        $this->actingAs($customer)->post(route('customer.requests.reschedule', $request), $this->validPayload());
        $proposal = RescheduleRequest::sole();

        $this->actingAs($customer)->post(route('worker.reschedule.accept', $proposal))->assertForbidden();
        $this->assertSame('pending', $proposal->fresh()->status);
    }

    /* -------------------------------- accept ------------------------------- */

    public function test_accept_updates_the_appointment_date_and_time(): void
    {
        ['customer' => $customer, 'worker' => $worker, 'request' => $request, 'appointment' => $appointment] = $this->scheduledJob();
        $this->actingAs($customer)->post(route('customer.requests.reschedule', $request), $this->validPayload([
            'proposed_date' => now()->addDays(9)->toDateString(),
            'proposed_time' => '16:30',
        ]));
        $proposal = RescheduleRequest::sole();

        $this->actingAs($worker)->post(route('worker.reschedule.accept', $proposal))->assertRedirect();

        $appointment->refresh();
        $this->assertSame(now()->addDays(9)->toDateString(), $appointment->scheduled_date->toDateString());
        $this->assertSame('16:30', $appointment->scheduled_time);
    }

    public function test_accept_sets_appointment_status_to_rescheduled(): void
    {
        ['customer' => $customer, 'worker' => $worker, 'request' => $request, 'appointment' => $appointment] = $this->scheduledJob();
        $this->actingAs($customer)->post(route('customer.requests.reschedule', $request), $this->validPayload());
        $proposal = RescheduleRequest::sole();

        $this->actingAs($worker)->post(route('worker.reschedule.accept', $proposal))->assertRedirect();

        $this->assertSame('rescheduled', $appointment->fresh()->status);
    }

    public function test_accept_leaves_service_request_status_as_scheduled(): void
    {
        ['customer' => $customer, 'worker' => $worker, 'request' => $request] = $this->scheduledJob();
        $this->actingAs($customer)->post(route('customer.requests.reschedule', $request), $this->validPayload());
        $proposal = RescheduleRequest::sole();

        $this->actingAs($worker)->post(route('worker.reschedule.accept', $proposal))->assertRedirect();

        $this->assertSame('scheduled', $request->fresh()->status);
        $this->assertSame('accepted', $proposal->fresh()->status);
        $this->assertNotNull($proposal->fresh()->responded_at);
    }

    public function test_accept_records_activity_and_notifications(): void
    {
        ['customer' => $customer, 'worker' => $worker, 'request' => $request] = $this->scheduledJob();
        $this->actingAs($customer)->post(route('customer.requests.reschedule', $request), $this->validPayload());
        $proposal = RescheduleRequest::sole();

        $this->actingAs($worker)->post(route('worker.reschedule.accept', $proposal))->assertRedirect();

        $this->assertTrue(ActivityLog::where('action', 'request.reschedule_requested')->exists());
        $this->assertTrue(ActivityLog::where('action', 'request.reschedule_accepted')->exists());
        $this->assertTrue(Notification::where('user_id', $customer->id)->where('type', 'reschedule_accepted')->exists());
        $this->assertTrue(Notification::where('user_id', $worker->id)->where('type', 'reschedule_requested')->exists());
        // The generic workflow notification/log from RequestWorkflow::transition() is preserved, not suppressed.
        $this->assertTrue(ActivityLog::where('action', 'request.status_changed')->exists());
    }

    /* ------------------------------- decline -------------------------------- */

    public function test_decline_leaves_the_appointment_completely_unchanged(): void
    {
        ['customer' => $customer, 'worker' => $worker, 'request' => $request, 'appointment' => $appointment] = $this->scheduledJob();
        $before = $appointment->only(['scheduled_date', 'scheduled_time', 'status']);
        $this->actingAs($customer)->post(route('customer.requests.reschedule', $request), $this->validPayload());
        $proposal = RescheduleRequest::sole();

        $this->actingAs($worker)
            ->post(route('worker.reschedule.decline', $proposal), ['decline_reason' => 'Not available that day.'])
            ->assertRedirect();

        $appointment->refresh();
        $this->assertSame($before['status'], $appointment->status);
        $this->assertSame($before['scheduled_time'], $appointment->scheduled_time);
        $this->assertSame('scheduled', $request->fresh()->status);
    }

    public function test_decline_records_activity_and_notification(): void
    {
        ['customer' => $customer, 'worker' => $worker, 'request' => $request] = $this->scheduledJob();
        $this->actingAs($customer)->post(route('customer.requests.reschedule', $request), $this->validPayload());
        $proposal = RescheduleRequest::sole();

        $this->actingAs($worker)
            ->post(route('worker.reschedule.decline', $proposal), ['decline_reason' => 'Not available that day.'])
            ->assertRedirect();

        $proposal->refresh();
        $this->assertSame('declined', $proposal->status);
        $this->assertSame('Not available that day.', $proposal->decline_reason);
        $this->assertNotNull($proposal->responded_at);
        $this->assertTrue(ActivityLog::where('action', 'request.reschedule_declined')->exists());
        $this->assertTrue(Notification::where('user_id', $customer->id)->where('type', 'reschedule_declined')->exists());
    }

    public function test_decline_reason_is_optional(): void
    {
        ['customer' => $customer, 'worker' => $worker, 'request' => $request] = $this->scheduledJob();
        $this->actingAs($customer)->post(route('customer.requests.reschedule', $request), $this->validPayload());
        $proposal = RescheduleRequest::sole();

        $this->actingAs($worker)->post(route('worker.reschedule.decline', $proposal))->assertRedirect();

        $this->assertSame('declined', $proposal->fresh()->status);
        $this->assertNull($proposal->fresh()->decline_reason);
    }

    /* ------------------------------ supersede ------------------------------- */

    public function test_worker_unilateral_reschedule_supersedes_a_pending_proposal(): void
    {
        ['customer' => $customer, 'worker' => $worker, 'request' => $request] = $this->scheduledJob();
        $this->actingAs($customer)->post(route('customer.requests.reschedule', $request), $this->validPayload());
        $proposal = RescheduleRequest::sole();

        $this->actingAs($worker)->post(route('worker.jobs.status', $request), [
            'status' => 'scheduled',
            'scheduled_date' => now()->addDays(4)->toDateString(),
            'scheduled_time' => '11:00',
        ])->assertRedirect();

        $this->assertSame('superseded', $proposal->fresh()->status);
        $this->assertNotNull($proposal->fresh()->responded_at);
        $this->assertTrue(ActivityLog::where('action', 'request.reschedule_superseded')->exists());
        $this->assertTrue(Notification::where('user_id', $customer->id)->where('type', 'reschedule_superseded')->exists());
    }

    public function test_a_superseded_proposal_is_no_longer_pending_and_cannot_be_responded_to(): void
    {
        ['customer' => $customer, 'worker' => $worker, 'request' => $request] = $this->scheduledJob();
        $this->actingAs($customer)->post(route('customer.requests.reschedule', $request), $this->validPayload());
        $proposal = RescheduleRequest::sole();

        $this->actingAs($worker)->post(route('worker.jobs.status', $request), [
            'status' => 'scheduled',
            'scheduled_date' => now()->addDays(4)->toDateString(),
            'scheduled_time' => '11:00',
        ]);

        $this->actingAs($worker)
            ->from(route('worker.jobs.show', $request))
            ->post(route('worker.reschedule.accept', $proposal))
            ->assertSessionHasErrors('status');

        // A new proposal can be submitted after the old one was superseded.
        $this->actingAs($customer)
            ->post(route('customer.requests.reschedule', $request), $this->validPayload())
            ->assertRedirect()
            ->assertSessionHas('success');
    }

    public function test_existing_worker_unilateral_first_time_scheduling_still_works_with_no_pending_proposal(): void
    {
        $customer = User::factory()->customer()->create();
        $worker = User::factory()->worker()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('accepted')->create([
            'worker_id' => $worker->id,
        ]);

        $this->actingAs($worker)->post(route('worker.jobs.status', $request), [
            'status' => 'scheduled',
            'scheduled_date' => now()->addDays(3)->toDateString(),
            'scheduled_time' => '09:00',
        ])->assertRedirect();

        $this->assertDatabaseHas('appointments', [
            'service_request_id' => $request->id,
            'status' => 'scheduled',
        ]);
        $this->assertDatabaseCount('reschedule_requests', 0);
    }

    /* --------------------------- financial isolation ------------------------- */

    public function test_rescheduling_has_zero_payment_invoice_or_quotation_side_effects(): void
    {
        ['customer' => $customer, 'worker' => $worker, 'request' => $request] = $this->scheduledJob();
        $this->actingAs($customer)->post(route('customer.requests.reschedule', $request), $this->validPayload());
        $proposal = RescheduleRequest::sole();

        $this->actingAs($worker)->post(route('worker.reschedule.accept', $proposal))->assertRedirect();

        $this->assertSame(0, Payment::count());
        $this->assertSame(0, Invoice::count());
        $this->assertSame(0, Quotation::count());
        $this->assertNull($request->fresh()->quoted_total);
        $this->assertNull($request->fresh()->final_total);
    }

    /* ------------------------------ localization ---------------------------- */

    public function test_the_reschedule_section_renders_in_english_with_no_raw_keys(): void
    {
        ['customer' => $customer, 'worker' => $worker, 'request' => $request] = $this->scheduledJob();
        $this->actingAs($customer)->post(route('customer.requests.reschedule', $request), $this->validPayload());

        $customerHtml = $this->actingAs($customer)->get(route('customer.requests.show', $request))
            ->assertOk()->getContent();
        $this->assertStringContainsString(__('requests.reschedule_pending_title', [], 'en'), $customerHtml);
        $this->assertStringNotContainsString('requests.reschedule_', $customerHtml);

        $workerHtml = $this->actingAs($worker)->get(route('worker.jobs.show', $request))
            ->assertOk()->getContent();
        $this->assertStringContainsString(__('workers.reschedule_pending_title', [], 'en'), $workerHtml);
        $this->assertStringNotContainsString('workers.reschedule_', $workerHtml);
    }

    public function test_the_reschedule_section_renders_in_arabic_with_rtl_and_no_raw_keys(): void
    {
        ['customer' => $customer, 'worker' => $worker, 'request' => $request] = $this->scheduledJob();
        $this->actingAs($customer)->post(route('customer.requests.reschedule', $request), $this->validPayload());

        $customerHtml = $this->actingAs($customer)->withSession(['locale' => 'ar'])
            ->get(route('customer.requests.show', $request))->assertOk()->getContent();
        $this->assertStringContainsString('<html lang="ar" dir="rtl">', $customerHtml);
        $this->assertStringContainsString(__('requests.reschedule_pending_title', [], 'ar'), $customerHtml);
        $this->assertStringNotContainsString('requests.reschedule_', $customerHtml);

        $workerHtml = $this->actingAs($worker)->withSession(['locale' => 'ar'])
            ->get(route('worker.jobs.show', $request))->assertOk()->getContent();
        $this->assertStringContainsString('<html lang="ar" dir="rtl">', $workerHtml);
        $this->assertStringContainsString(__('workers.reschedule_pending_title', [], 'ar'), $workerHtml);
        $this->assertStringNotContainsString('workers.reschedule_', $workerHtml);
    }

    /* ------------------- ATTACK POINT #2B: cancellation interaction ------------------- */

    public function test_cancelling_a_request_supersedes_its_pending_reschedule_proposal(): void
    {
        ['customer' => $customer, 'request' => $request] = $this->scheduledJob();
        $this->actingAs($customer)->post(route('customer.requests.reschedule', $request), $this->validPayload());
        $proposal = RescheduleRequest::sole();

        $this->actingAs($customer)->post(route('customer.requests.cancel', $request))->assertRedirect();

        $this->assertSame('superseded', $proposal->fresh()->status);
        $this->assertNotNull($proposal->fresh()->responded_at);
        $this->assertSame('cancelled', $request->fresh()->status);
    }

    /** @dataProvider resolvedRescheduleStatuses */
    public function test_cancellation_does_not_touch_an_already_resolved_reschedule_proposal(string $status): void
    {
        ['customer' => $customer, 'request' => $request] = $this->scheduledJob();
        $proposal = RescheduleRequest::factory()->for($request, 'request')->create([
            'status' => $status,
            'responded_at' => now()->subDay(),
        ]);

        $this->actingAs($customer)->post(route('customer.requests.cancel', $request))->assertRedirect();

        $this->assertSame($status, $proposal->fresh()->status);
        $this->assertTrue($proposal->fresh()->responded_at->equalTo($proposal->responded_at));
    }

    public static function resolvedRescheduleStatuses(): array
    {
        return [['accepted'], ['declined'], ['superseded']];
    }

    public function test_the_worker_cannot_accept_a_reschedule_after_the_parent_request_is_cancelled(): void
    {
        ['customer' => $customer, 'worker' => $worker, 'request' => $request] = $this->scheduledJob();
        $this->actingAs($customer)->post(route('customer.requests.reschedule', $request), $this->validPayload());
        $proposal = RescheduleRequest::sole();

        // Cancel via the workflow directly so the proposal itself stays
        // "pending" for this test — isolating the accept() guard specifically,
        // independent of the new cancel()-side supersede behaviour above.
        $request->update(['status' => 'cancelled']);

        $this->actingAs($worker)
            ->from(route('worker.jobs.show', $request))
            ->post(route('worker.reschedule.accept', $proposal))
            ->assertSessionHasErrors('status');

        $this->assertSame('pending', $proposal->fresh()->status);
    }

    public function test_the_worker_cannot_decline_a_reschedule_after_the_parent_request_is_cancelled(): void
    {
        ['customer' => $customer, 'worker' => $worker, 'request' => $request] = $this->scheduledJob();
        $this->actingAs($customer)->post(route('customer.requests.reschedule', $request), $this->validPayload());
        $proposal = RescheduleRequest::sole();

        $request->update(['status' => 'cancelled']);

        $this->actingAs($worker)
            ->from(route('worker.jobs.show', $request))
            ->post(route('worker.reschedule.decline', $proposal))
            ->assertSessionHasErrors('status');

        $this->assertSame('pending', $proposal->fresh()->status);
    }

    public function test_normal_worker_decline_while_still_scheduled_is_unaffected_by_the_new_guard(): void
    {
        ['customer' => $customer, 'worker' => $worker, 'request' => $request] = $this->scheduledJob();
        $this->actingAs($customer)->post(route('customer.requests.reschedule', $request), $this->validPayload());
        $proposal = RescheduleRequest::sole();

        $this->actingAs($worker)->post(route('worker.reschedule.decline', $proposal))->assertRedirect();

        $this->assertSame('declined', $proposal->fresh()->status);
        $this->assertSame('scheduled', $request->fresh()->status);
    }

    public function test_the_worker_job_page_hides_the_reschedule_card_once_the_request_is_cancelled(): void
    {
        ['customer' => $customer, 'worker' => $worker, 'request' => $request] = $this->scheduledJob();
        $this->actingAs($customer)->post(route('customer.requests.reschedule', $request), $this->validPayload());

        // Force a stale pending proposal on an already-cancelled request,
        // bypassing the new cancel()-side supersede — a defensive UI guard
        // must hold even if that invariant were ever violated some other way.
        $request->update(['status' => 'cancelled']);

        $this->actingAs($worker)->get(route('worker.jobs.show', $request))
            ->assertOk()
            ->assertDontSee(__('workers.reschedule_pending_title'))
            ->assertDontSee(route('worker.reschedule.accept', RescheduleRequest::sole()), false);
    }

    /* ------------------- ATTACK POINT #3: collision on accept ------------------- */

    public function test_accepting_a_proposal_that_collides_with_the_workers_other_appointment_is_rejected(): void
    {
        ['customer' => $customer, 'worker' => $worker, 'request' => $request] = $this->scheduledJob();
        $collisionDate = now()->addDays(6)->toDateString();

        // The same worker already has another, unrelated job at that exact slot.
        $otherRequest = ServiceRequest::factory()->for(User::factory()->customer()->create(), 'customer')
            ->status('scheduled')->create(['worker_id' => $worker->id]);
        Appointment::create([
            'service_request_id' => $otherRequest->id,
            'worker_id' => $worker->id,
            'scheduled_date' => $collisionDate,
            'scheduled_time' => '13:00',
            'status' => 'scheduled',
        ]);

        $this->actingAs($customer)->post(route('customer.requests.reschedule', $request), $this->validPayload([
            'proposed_date' => $collisionDate,
            'proposed_time' => '13:00',
        ]));
        $proposal = RescheduleRequest::sole();

        $this->actingAs($worker)
            ->from(route('worker.jobs.show', $request))
            ->post(route('worker.reschedule.accept', $proposal))
            ->assertSessionHasErrors('status');
    }

    public function test_a_rejected_collision_leaves_the_proposal_appointment_and_request_untouched(): void
    {
        ['customer' => $customer, 'worker' => $worker, 'request' => $request, 'appointment' => $appointment] = $this->scheduledJob();
        $beforeDate = $appointment->scheduled_date->toDateString();
        $beforeTime = $appointment->scheduled_time;
        $beforeStatus = $appointment->status;
        $collisionDate = now()->addDays(6)->toDateString();

        $otherRequest = ServiceRequest::factory()->for(User::factory()->customer()->create(), 'customer')
            ->status('scheduled')->create(['worker_id' => $worker->id]);
        Appointment::create([
            'service_request_id' => $otherRequest->id,
            'worker_id' => $worker->id,
            'scheduled_date' => $collisionDate,
            'scheduled_time' => '13:00',
            'status' => 'scheduled',
        ]);

        $this->actingAs($customer)->post(route('customer.requests.reschedule', $request), $this->validPayload([
            'proposed_date' => $collisionDate,
            'proposed_time' => '13:00',
        ]));
        $proposal = RescheduleRequest::sole();

        $this->actingAs($worker)->post(route('worker.reschedule.accept', $proposal));

        $this->assertSame('pending', $proposal->fresh()->status);
        $appointment->refresh();
        $this->assertSame($beforeDate, $appointment->scheduled_date->toDateString());
        $this->assertSame($beforeTime, $appointment->scheduled_time);
        $this->assertSame($beforeStatus, $appointment->status);
        $this->assertSame('scheduled', $request->fresh()->status);
    }

    public function test_a_non_conflicting_proposal_can_still_be_accepted_normally(): void
    {
        ['customer' => $customer, 'worker' => $worker, 'request' => $request, 'appointment' => $appointment] = $this->scheduledJob();

        // The worker's other job is scheduled at a different time entirely.
        $otherRequest = ServiceRequest::factory()->for(User::factory()->customer()->create(), 'customer')
            ->status('scheduled')->create(['worker_id' => $worker->id]);
        Appointment::create([
            'service_request_id' => $otherRequest->id,
            'worker_id' => $worker->id,
            'scheduled_date' => now()->addDays(6)->toDateString(),
            'scheduled_time' => '08:00',
            'status' => 'scheduled',
        ]);

        $this->actingAs($customer)->post(route('customer.requests.reschedule', $request), $this->validPayload([
            'proposed_date' => now()->addDays(6)->toDateString(),
            'proposed_time' => '17:00',
        ]));
        $proposal = RescheduleRequest::sole();

        $this->actingAs($worker)->post(route('worker.reschedule.accept', $proposal))->assertRedirect();

        $this->assertSame('accepted', $proposal->fresh()->status);
        $appointment->refresh();
        $this->assertSame('17:00', $appointment->scheduled_time);
    }

    /* ------------------- ATTACK POINT #4 / F2: stale proposal on accept ------------------- */

    public function test_a_pending_proposal_whose_time_has_already_passed_cannot_be_accepted(): void
    {
        Carbon::setTestNow('2026-06-15 14:00:00');
        ['worker' => $worker, 'request' => $request] = $this->scheduledJob();
        // Simulates a proposal that sat unanswered long enough for its own
        // proposed slot to lapse — StoreRescheduleRequestRequest already
        // forbids creating one like this via the normal route.
        $proposal = RescheduleRequest::factory()->for($request, 'request')->create([
            'status' => 'pending',
            'proposed_date' => '2026-06-15',
            'proposed_time' => '09:00',
        ]);

        $this->actingAs($worker)
            ->from(route('worker.jobs.show', $request))
            ->post(route('worker.reschedule.accept', $proposal))
            ->assertSessionHasErrors('status');
    }

    public function test_a_stale_proposal_stays_pending_and_leaves_the_appointment_and_request_untouched(): void
    {
        Carbon::setTestNow('2026-06-15 14:00:00');
        ['worker' => $worker, 'request' => $request, 'appointment' => $appointment] = $this->scheduledJob();
        $beforeDate = $appointment->scheduled_date->toDateString();
        $beforeTime = $appointment->scheduled_time;
        $proposal = RescheduleRequest::factory()->for($request, 'request')->create([
            'status' => 'pending',
            'proposed_date' => '2026-06-15',
            'proposed_time' => '09:00',
        ]);

        $this->actingAs($worker)->post(route('worker.reschedule.accept', $proposal));

        $this->assertSame('pending', $proposal->fresh()->status);
        $appointment->refresh();
        $this->assertSame($beforeDate, $appointment->scheduled_date->toDateString());
        $this->assertSame($beforeTime, $appointment->scheduled_time);
        $this->assertSame('scheduled', $request->fresh()->status);
    }

    public function test_rejecting_a_stale_proposal_creates_no_notification_or_activity_log(): void
    {
        Carbon::setTestNow('2026-06-15 14:00:00');
        ['worker' => $worker, 'request' => $request] = $this->scheduledJob();
        $proposal = RescheduleRequest::factory()->for($request, 'request')->create([
            'status' => 'pending',
            'proposed_date' => '2026-06-15',
            'proposed_time' => '09:00',
        ]);

        $this->actingAs($worker)->post(route('worker.reschedule.accept', $proposal));

        $this->assertFalse(ActivityLog::where('action', 'request.reschedule_accepted')->exists());
        $this->assertFalse(Notification::where('type', 'reschedule_accepted')->exists());
    }

    public function test_a_still_future_proposal_can_be_accepted_normally_at_a_fixed_clock(): void
    {
        Carbon::setTestNow('2026-06-15 14:00:00');
        ['worker' => $worker, 'request' => $request] = $this->scheduledJob();
        $proposal = RescheduleRequest::factory()->for($request, 'request')->create([
            'status' => 'pending',
            'proposed_date' => '2026-06-16',
            'proposed_time' => '09:00',
        ]);

        $this->actingAs($worker)->post(route('worker.reschedule.accept', $proposal))->assertRedirect();

        $this->assertSame('accepted', $proposal->fresh()->status);
        $this->assertSame('scheduled', $request->fresh()->status);
    }
}
