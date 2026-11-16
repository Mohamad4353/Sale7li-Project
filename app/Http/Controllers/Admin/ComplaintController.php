<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateComplaintRequest;
use App\Models\Complaint;
use App\Models\Notification;
use Illuminate\Http\Request;

class ComplaintController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->string('status')->value();
        $q = $request->string('q')->value();

        $complaints = Complaint::query()
            ->with(['reporter:id,name', 'request:id,reference'])
            ->when(in_array($status, UpdateComplaintRequest::STATUSES, true), fn ($x) => $x->where('status', $status))
            ->when($q, fn ($x) => $x->where(fn ($w) => $w
                ->whereHas('request', fn ($r) => $r->where('reference', 'like', '%'.$q.'%'))
                ->orWhereHas('reporter', fn ($r) => $r->where('name', 'like', '%'.$q.'%')->orWhere('email', 'like', '%'.$q.'%'))))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.complaints.index', compact('complaints', 'status', 'q'));
    }

    public function show(Complaint $complaint)
    {
        $complaint->load(['reporter', 'request.customer', 'request.worker', 'request.category']);

        return view('admin.complaints.show', compact('complaint'));
    }

    /**
     * Update workflow state only: status + internal notes. A complaint never
     * mutates payment, invoice, refund or commission data — any refund still
     * goes through the existing RefundService from the payments screen.
     */
    public function update(UpdateComplaintRequest $request, Complaint $complaint)
    {
        $statusChanged = array_key_exists('status', $request->validated())
            && $request->validated()['status'] !== $complaint->status;

        $complaint->update($request->validated());

        // Notify the person who raised it (and the assigned professional, if any)
        // only when the status actually moved. Internal notes stay internal.
        if ($statusChanged) {
            $complaint->loadMissing('request');
            $data = [
                'reference' => $complaint->request?->reference,
                'complaint_status' => $complaint->status,
            ];
            Notification::record($complaint->reporter_id, 'complaint_update', $complaint->service_request_id, $data);

            // The assigned professional did not file the complaint, so their copy
            // uses non-reporter wording (complaint_update_worker) — same payload.
            if ($complaint->request?->worker_id && $complaint->request->worker_id !== $complaint->reporter_id) {
                Notification::record($complaint->request->worker_id, 'complaint_update_worker', $complaint->service_request_id, $data);
            }
        }

        return redirect()
            ->route('admin.complaints.show', $complaint)
            ->with('success', __('complaints.flash.updated'));
    }
}
