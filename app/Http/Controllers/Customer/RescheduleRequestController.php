<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRescheduleRequestRequest;
use App\Models\ActivityLog;
use App\Models\Notification;
use App\Models\ServiceRequest;

class RescheduleRequestController extends Controller
{
    /**
     * A customer proposes a different appointment date/time on their own
     * `scheduled` request. This never touches ServiceRequest.status or
     * RequestWorkflow — it only creates a proposal for the assigned
     * professional to accept or decline (see Worker\RescheduleResponseController).
     */
    public function store(StoreRescheduleRequestRequest $request, ServiceRequest $serviceRequest)
    {
        if ($serviceRequest->status !== 'scheduled') {
            return back()->withErrors(['status' => __('messages.flash.reschedule_not_available')]);
        }

        if ($serviceRequest->rescheduleRequests()->where('status', 'pending')->exists()) {
            return back()->withErrors(['status' => __('messages.flash.reschedule_already_pending')]);
        }

        $data = $request->validated();

        $proposal = $serviceRequest->rescheduleRequests()->create([
            'proposed_date' => $data['proposed_date'],
            'proposed_time' => $data['proposed_time'],
            'reason' => $data['reason'] ?? null,
            'status' => 'pending',
        ]);

        if ($serviceRequest->worker_id) {
            Notification::record($serviceRequest->worker_id, 'reschedule_requested', $serviceRequest->id, [
                'reference' => $serviceRequest->reference,
            ]);
        }

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'request.reschedule_requested',
            'subject_type' => ServiceRequest::class,
            'subject_id' => $serviceRequest->id,
            'metadata' => ['reschedule_request_id' => $proposal->id],
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', __('messages.flash.reschedule_requested'));
    }
}
