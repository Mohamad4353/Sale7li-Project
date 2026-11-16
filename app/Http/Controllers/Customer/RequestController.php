<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use App\Services\RequestWorkflow;
use App\Support\RequestTimeline;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use LogicException;

class RequestController extends Controller
{
    /**
     * States from which a customer is allowed to cancel their own request.
     * Once a professional is on the way or working, cancellation must go
     * through support rather than a self-service action.
     */
    private const CANCELLABLE_STATES = ['pending', 'accepted', 'scheduled'];

    /** Allow-listed cancellation reason codes; labels live in the requests lang files. */
    public const CANCELLATION_REASONS = [
        'no_longer_needed', 'found_another_provider', 'schedule_changed', 'created_by_mistake', 'other',
    ];

    /**
     * UI status groups → existing workflow states (see App\Services\RequestWorkflow).
     * The list has no state machine of its own; it only filters.
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
            ->customerRequests()
            ->with(['category', 'worker.workerProfile'])
            ->when($status, fn ($query) => $query->whereIn('status', self::STATUS_FILTERS[$status]))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('customer.requests.index', compact('requests', 'status'));
    }

    public function show(ServiceRequest $serviceRequest)
    {
        $this->authorize('view', $serviceRequest);

        $serviceRequest->load([
            'category', 'worker.workerProfile', 'address', 'appointment',
            'quotations.worker', 'payment.refunds', 'invoice', 'review', 'rescheduleRequests',
        ]);

        return view('customer.requests.show', [
            'serviceRequest' => $serviceRequest,
            'timeline' => RequestTimeline::for($serviceRequest),
        ]);
    }

    public function cancel(Request $request, ServiceRequest $serviceRequest, RequestWorkflow $workflow)
    {
        $this->authorize('cancel', $serviceRequest);

        $validated = $request->validate([
            'reason' => ['nullable', Rule::in(self::CANCELLATION_REASONS)],
        ]);

        if (! in_array($serviceRequest->status, self::CANCELLABLE_STATES, true)) {
            return back()->withErrors([
                'status' => __('messages.flash.request_cannot_cancel'),
            ]);
        }

        try {
            DB::transaction(function () use ($serviceRequest, $validated, $workflow) {
                // Attribution is fixed server-side: a customer using this route
                // is, by definition, a customer-initiated cancellation.
                $workflow->transition($serviceRequest, 'cancelled', 'customer', $validated['reason'] ?? null);

                // A cancelled request must not leave a live-looking appointment
                // or an actionable pending reschedule proposal behind. The date/
                // time themselves are left untouched as historical data.
                $serviceRequest->appointment?->update(['status' => 'cancelled']);

                $serviceRequest->rescheduleRequests()
                    ->where('status', 'pending')
                    ->update(['status' => 'superseded', 'responded_at' => now()]);
            });
        } catch (LogicException $e) {
            return back()->withErrors([
                'status' => __('messages.flash.request_cannot_cancel'),
            ]);
        }

        return back()->with('success', __('messages.flash.request_cancelled'));
    }
}
