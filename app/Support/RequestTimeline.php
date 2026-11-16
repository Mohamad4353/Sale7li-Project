<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\ServiceRequest;
use Illuminate\Support\Carbon;

/**
 * Reconstructs the timestamped lifecycle of a service request from the
 * ActivityLog rows RequestWorkflow already writes on every transition. This is
 * a read-only projection of existing history — no new storage, no fabricated
 * timestamps. A key is simply absent when the data model holds no time for it.
 */
class RequestTimeline
{
    /**
     * @return array<string, Carbon> keyed by 'submitted', 'assigned', and each
     *                               request status that has a recorded transition.
     */
    public static function for(ServiceRequest $request): array
    {
        $logs = ActivityLog::query()
            ->where('subject_type', ServiceRequest::class)
            ->where('subject_id', $request->id)
            ->orderBy('id')
            ->get(['action', 'metadata', 'created_at']);

        $at = [];

        // Submitted — the creation row, else the request's own created_at.
        $at['submitted'] = $logs->firstWhere('action', 'request.created')?->created_at
            ?? $request->created_at;

        // Assigned — the first time a professional was attached.
        if ($assignedLog = $logs->firstWhere('action', 'request.assigned')) {
            $at['assigned'] = $assignedLog->created_at;
        }

        // Status changes — keep the first time the request entered each state
        // (a request can re-enter 'scheduled' after being rescheduled).
        foreach ($logs->where('action', 'request.status_changed') as $log) {
            $status = $log->metadata['status'] ?? null;
            if ($status !== null && ! isset($at[$status])) {
                $at[$status] = $log->created_at;
            }
        }

        // Completion has an authoritative column; prefer it when present.
        if ($request->completed_at !== null) {
            $at['completed'] = $request->completed_at;
        }

        return $at;
    }
}
