<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreComplaintRequest;
use App\Models\Complaint;
use App\Models\ServiceRequest;

class ComplaintController extends Controller
{
    /**
     * The problem reports this customer has filed. Scoped by reporter_id — the
     * list can only ever contain the caller's own complaints. `internal_notes`
     * is never selected or shown.
     */
    public function index()
    {
        $complaints = Complaint::query()
            ->where('reporter_id', auth()->id())
            ->with(['request:id,reference,service_category_id', 'request.category:id,name,name_ar,slug'])
            ->latest()
            ->paginate(15);

        return view('customer.complaints.index', compact('complaints'));
    }

    /**
     * A single complaint the caller filed. ComplaintPolicy::view rejects any
     * other customer's complaint id.
     */
    public function show(Complaint $complaint)
    {
        $this->authorize('view', $complaint);

        $complaint->load(['request:id,reference,service_category_id', 'request.category:id,name,name_ar,slug']);

        return view('customer.complaints.show', compact('complaint'));
    }

    /**
     * File a problem report against the customer's own request. Only `category`
     * and `description` come from the form; `reporter_id`, `status` and
     * `priority` are set server-side. A complaint never touches payment,
     * invoice, refund or commission data.
     */
    public function store(StoreComplaintRequest $request, ServiceRequest $serviceRequest)
    {
        $data = $request->validated();

        Complaint::create([
            'service_request_id' => $serviceRequest->id,
            'reporter_id' => $request->user()->id,
            'category' => $data['category'],
            'description' => $data['description'],
            'priority' => 'medium',
            'status' => 'open',
        ]);

        return redirect()
            ->route('customer.requests.show', $serviceRequest)
            ->with('success', __('complaints.flash.submitted'));
    }
}
