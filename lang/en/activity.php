<?php

return [
    // Human-readable labels for the ActivityLog action codes written by
    // App\Services\RequestWorkflow, shown in the admin request activity feed.
    'request.created' => 'Request created',
    'request.assigned' => 'Professional assigned',
    'request.declined' => 'Professional declined the job',
    'request.status_changed' => 'Status changed',
    'request.reschedule_requested' => 'Customer proposed a new appointment time',
    'request.reschedule_accepted' => 'Professional accepted the proposed appointment time',
    'request.reschedule_declined' => 'Professional declined the proposed appointment time',
    'request.reschedule_superseded' => 'Reschedule proposal superseded by a new appointment time',

    // Used when a status-change entry carries its target state in metadata.
    'status_changed_to' => 'Status changed to :status',
];
