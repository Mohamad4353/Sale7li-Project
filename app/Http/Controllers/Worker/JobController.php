<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQuotationRequest;
use App\Http\Requests\UpdateJobStatusRequest;
use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\Notification;
use App\Models\Quotation;
use App\Models\ServiceRequest;
use App\Payments\Money;
use App\Services\RequestWorkflow;
use App\Support\RequestTimeline;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use LogicException;

class JobController extends Controller
{
    /**
     * UI status groups → existing workflow states (see App\Services\RequestWorkflow).
     * Same concept as the customer request list; the list only filters.
     */
    private const STATUS_FILTERS = [
        'active' => ['pending', 'accepted', 'scheduled', 'on_the_way', 'in_progress'],
        'completed' => ['completed'],
        'cancelled' => ['cancelled', 'rejected'],
    ];

    public function index(Request $request)
    {
        $status = $request->string('status')->value();
        $status = array_key_exists($status, self::STATUS_FILTERS) ? $status : null;

        $requests = auth()->user()
            ->workerRequests()
            ->with(['category', 'customer', 'address', 'appointment'])
            ->when($status, fn ($query) => $query->whereIn('status', self::STATUS_FILTERS[$status]))
            ->orderByDesc('is_emergency')   // customer-flagged emergencies surface first
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('worker.jobs.index', compact('requests', 'status'));
    }

    public function show(ServiceRequest $serviceRequest, RequestWorkflow $workflow)
    {
        $this->authorize('view', $serviceRequest);

        $serviceRequest->load(['category', 'customer', 'address', 'appointment', 'quotations.worker', 'rescheduleRequests']);
        $nextStatuses = $workflow->allowedNextStatuses($serviceRequest);
        $timeline = RequestTimeline::for($serviceRequest);

        return view('worker.jobs.show', compact('serviceRequest', 'nextStatuses', 'timeline'));
    }

    public function accept(ServiceRequest $serviceRequest, RequestWorkflow $workflow)
    {
        $this->authorize('acceptAssignment', $serviceRequest);

        try {
            $workflow->workerAccept($serviceRequest, auth()->user());
        } catch (LogicException $e) {
            return back()->withErrors(['status' => __('messages.flash.job_unavailable')]);
        }

        return back()->with('success', __('messages.flash.job_accepted'));
    }

    public function decline(ServiceRequest $serviceRequest, RequestWorkflow $workflow)
    {
        $this->authorize('acceptAssignment', $serviceRequest);

        try {
            $workflow->workerDecline($serviceRequest, auth()->user());
        } catch (LogicException $e) {
            return back()->withErrors(['status' => __('messages.flash.job_unavailable')]);
        }

        return back()->with('success', __('messages.flash.job_declined'));
    }

    public function status(UpdateJobStatusRequest $request, ServiceRequest $serviceRequest, RequestWorkflow $workflow)
    {
        $data = $request->validated();
        $status = $data['status'];

        if ($status === 'scheduled' && (empty($data['scheduled_date']) || empty($data['scheduled_time']))) {
            return back()->withErrors(['scheduled_date' => __('messages.flash.schedule_required')]);
        }

        if (! in_array($status, $workflow->allowedNextStatuses($serviceRequest), true)) {
            return back()->withErrors(['status' => __('messages.flash.status_change_blocked')]);
        }

        if ($status === 'scheduled' && $this->workerHasCollidingAppointment(
            auth()->id(), $data['scheduled_date'], $data['scheduled_time'], $serviceRequest->id
        )) {
            return back()->withErrors(['scheduled_time' => __('messages.flash.slot_already_booked')]);
        }

        try {
            DB::transaction(function () use ($serviceRequest, $data, $status, $workflow, $request) {
                if ($status === 'scheduled') {
                    // Captured before the update so a notification/log entry can
                    // name each proposal this unilateral change makes moot.
                    $supersededProposals = $serviceRequest->rescheduleRequests()->where('status', 'pending')->get();

                    Appointment::updateOrCreate(
                        ['service_request_id' => $serviceRequest->id],
                        [
                            'worker_id' => auth()->id(),
                            'scheduled_date' => $data['scheduled_date'],
                            'scheduled_time' => $data['scheduled_time'],
                            'status' => $serviceRequest->appointment?->exists ? 'rescheduled' : 'scheduled',
                        ]
                    );

                    // The worker just set the appointment through this existing
                    // control — any customer proposal still awaiting a response
                    // is no longer actionable. History is preserved, not deleted.
                    foreach ($supersededProposals as $proposal) {
                        $proposal->update(['status' => 'superseded', 'responded_at' => now()]);

                        Notification::record($serviceRequest->customer_id, 'reschedule_superseded', $serviceRequest->id, [
                            'reference' => $serviceRequest->reference,
                        ]);

                        ActivityLog::create([
                            'user_id' => auth()->id(),
                            'action' => 'request.reschedule_superseded',
                            'subject_type' => ServiceRequest::class,
                            'subject_id' => $serviceRequest->id,
                            'metadata' => ['reschedule_request_id' => $proposal->id],
                            'ip_address' => $request->ip(),
                        ]);
                    }
                }

                if ($status === 'completed') {
                    $paths = [];
                    foreach ($request->file('completion_images', []) as $file) {
                        $paths[] = $file->store('completions', 'local');
                    }

                    $serviceRequest->update([
                        'completion_notes' => $data['completion_notes'] ?? null,
                        'completion_images' => $paths,
                    ]);

                    $serviceRequest->appointment?->update(['status' => 'completed']);
                }

                // The `final_total` snapshot is frozen inside the completion
                // transition (RequestWorkflow), not here.
                $workflow->transition($serviceRequest, $status);
            });
        } catch (LogicException $e) {
            return back()->withErrors(['status' => __('messages.flash.status_change_blocked')]);
        }

        return back()->with('success', __('messages.flash.job_status_updated'));
    }

    public function quotation(StoreQuotationRequest $request, ServiceRequest $serviceRequest)
    {
        // Once a quotation has been accepted (or the job has been paid), the
        // priced amount is authoritative and locked — no further quotations.
        if ($serviceRequest->payment_status === 'paid'
            || $serviceRequest->quotations()->where('status', 'accepted')->exists()) {
            return back()->withErrors(['quotation' => __('messages.flash.quotation_locked')]);
        }

        $data = $request->validated();

        // Server-side total in integer cents — never trusts a posted `total`.
        // Formula unchanged: max(0, base + additional - discount + tax).
        $totalCents = max(
            0,
            Money::toCents($data['base_price'])
            + Money::toCents($data['additional_charges'] ?? 0)
            - Money::toCents($data['discount'] ?? 0)
            + Money::toCents($data['tax'] ?? 0)
        );
        $total = Money::toDecimalString($totalCents);

        Quotation::create([
            ...$data,
            'service_request_id' => $serviceRequest->id,
            'worker_id' => auth()->id(),
            'additional_charges' => $data['additional_charges'] ?? 0,
            'discount' => $data['discount'] ?? 0,
            'tax' => $data['tax'] ?? 0,
            'total' => $total,
        ]);

        // `quoted_total` on the request is only set when the customer accepts a
        // quotation (see Customer\QuotationController::accept) so that a submitted
        // — but not yet approved — quote can never be charged.

        Notification::record($serviceRequest->customer_id, 'quotation_received', $serviceRequest->id, [
            'reference' => $serviceRequest->reference,
            'amount' => Money::format($total),
        ]);

        return back()->with('success', __('messages.flash.quotation_submitted'));
    }

    /**
     * True when this worker already holds another active appointment (any
     * status except cancelled/completed) at the exact same date and time,
     * for a different service request. The collision source is real
     * Appointment rows — never WorkerAvailability or BookingAvailability.
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
