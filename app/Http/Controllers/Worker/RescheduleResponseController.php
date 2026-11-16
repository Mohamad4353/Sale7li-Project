<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeclineRescheduleRequestRequest;
use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\Notification;
use App\Models\RescheduleRequest;
use App\Models\ServiceRequest;
use App\Services\RequestWorkflow;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use LogicException;

class RescheduleResponseController extends Controller
{
    /**
     * Accept a customer's proposed appointment. Reuses the existing, already
     * -permitted `scheduled -> scheduled` self-transition (RequestWorkflow)
     * and the exact Appointment::updateOrCreate(...) shape the worker's own
     * manual reschedule already uses — RequestWorkflow itself is untouched.
     */
    public function accept(RescheduleRequest $rescheduleRequest, RequestWorkflow $workflow)
    {
        $this->authorize('respond', $rescheduleRequest);

        $serviceRequest = $rescheduleRequest->request;

        if ($rescheduleRequest->status !== 'pending' || $serviceRequest->status !== 'scheduled') {
            return back()->withErrors(['status' => __('messages.flash.reschedule_no_longer_pending')]);
        }

        // The proposed slot must not collide with this worker's OTHER active
        // appointments. A collision here does not decline or supersede the
        // proposal — it stays pending so the worker can resolve it later.
        if ($this->workerHasCollidingAppointment(
            auth()->id(), $rescheduleRequest->proposed_date->toDateString(), $rescheduleRequest->proposed_time, $serviceRequest->id
        )) {
            return back()->withErrors(['status' => __('messages.flash.slot_already_booked')]);
        }

        // A proposal that sat unanswered long enough for its own proposed
        // slot to lapse must not be silently accepted. It stays pending —
        // the worker can decline it or the customer can propose a new time.
        $proposedWhen = Carbon::parse($rescheduleRequest->proposed_date->toDateString().' '.$rescheduleRequest->proposed_time);
        if ($proposedWhen->lessThanOrEqualTo(now())) {
            return back()->withErrors(['status' => __('messages.flash.reschedule_time_has_passed')]);
        }

        try {
            DB::transaction(function () use ($rescheduleRequest, $serviceRequest, $workflow) {
                Appointment::updateOrCreate(
                    ['service_request_id' => $serviceRequest->id],
                    [
                        'worker_id' => auth()->id(),
                        'scheduled_date' => $rescheduleRequest->proposed_date,
                        'scheduled_time' => $rescheduleRequest->proposed_time,
                        'status' => 'rescheduled',
                    ]
                );

                $rescheduleRequest->update(['status' => 'accepted', 'responded_at' => now()]);

                // Fires the existing generic request_status notification/log as
                // a side effect — preserved deliberately, not suppressed.
                $workflow->transition($serviceRequest, 'scheduled');
            });
        } catch (LogicException $e) {
            return back()->withErrors(['status' => __('messages.flash.reschedule_no_longer_pending')]);
        }

        Notification::record($serviceRequest->customer_id, 'reschedule_accepted', $serviceRequest->id, [
            'reference' => $serviceRequest->reference,
        ]);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'request.reschedule_accepted',
            'subject_type' => ServiceRequest::class,
            'subject_id' => $serviceRequest->id,
            'metadata' => ['reschedule_request_id' => $rescheduleRequest->id],
            'ip_address' => request()->ip(),
        ]);

        return back()->with('success', __('messages.flash.reschedule_accepted'));
    }

    /**
     * Decline a customer's proposed appointment. The existing Appointment row
     * is never touched; RequestWorkflow is never called.
     */
    public function decline(DeclineRescheduleRequestRequest $request, RescheduleRequest $rescheduleRequest)
    {
        $serviceRequest = $rescheduleRequest->request;

        if ($rescheduleRequest->status !== 'pending' || $serviceRequest->status !== 'scheduled') {
            return back()->withErrors(['status' => __('messages.flash.reschedule_no_longer_pending')]);
        }

        $rescheduleRequest->update([
            'status' => 'declined',
            'decline_reason' => $request->validated()['decline_reason'] ?? null,
            'responded_at' => now(),
        ]);

        Notification::record($serviceRequest->customer_id, 'reschedule_declined', $serviceRequest->id, [
            'reference' => $serviceRequest->reference,
        ]);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'request.reschedule_declined',
            'subject_type' => ServiceRequest::class,
            'subject_id' => $serviceRequest->id,
            'metadata' => ['reschedule_request_id' => $rescheduleRequest->id],
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', __('messages.flash.reschedule_declined'));
    }

    /**
     * True when this worker already holds another active appointment (any
     * status except cancelled/completed) at the exact same date and time,
     * for a different service request. Mirrors JobController's guard —
     * the collision source is real Appointment rows only.
     */
    private function workerHasCollidingAppointment(int $workerId, string $date, string $time, int $excludingServiceRequestId): bool
    {
        return Appointment::where('worker_id', $workerId)
            ->whereDate('scheduled_date', $date)
            ->where('scheduled_time', $time)
            ->where('service_request_id', '!=', $excludingServiceRequestId)
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->exists();
    }
}
