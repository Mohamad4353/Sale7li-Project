<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Models\Complaint;

class ComplaintController extends Controller
{
    /**
     * Problem reports customers have filed about jobs assigned to this
     * professional. Scoped through the request's worker_id — the list can only
     * ever contain complaints on the caller's own jobs. `internal_notes` is
     * never selected or shown.
     */
    public function index()
    {
        $complaints = Complaint::query()
            ->whereHas('request', fn ($q) => $q->where('worker_id', auth()->id()))
            ->with([
                'request:id,reference,service_category_id',
                'request.category:id,name,name_ar,slug',
                'reporter:id,name',
            ])
            ->latest()
            ->paginate(15);

        return view('worker.complaints.index', compact('complaints'));
    }

    /**
     * A single complaint on one of the caller's jobs. ComplaintPolicy::view
     * rejects a complaint whose request is assigned to a different worker.
     */
    public function show(Complaint $complaint)
    {
        $this->authorize('view', $complaint);

        $complaint->load([
            'request:id,reference,service_category_id',
            'request.category:id,name,name_ar,slug',
            'reporter:id,name',
        ]);

        return view('worker.complaints.show', compact('complaint'));
    }
}
