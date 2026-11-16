<?php

return [
    'title' => 'Notifications',
    'sub' => 'Updates on your requests, messages, and account.',
    'mark_all_read' => 'Mark all as read',
    'unread' => 'New',
    'none_title' => "You're all caught up",
    'none_hint' => 'New notifications about your requests and messages will appear here.',

    // Unread messages badge on the "Messages" nav item.
    'unread_messages' => ':count unread message|:count unread messages',

    // Titles for system-generated notifications, keyed by the stored `type`.
    'types' => [
        'request_submitted' => 'Request submitted',
        'request_assigned' => 'New job to review',
        'professional_assigned' => 'Professional assigned',
        'professional_declined' => 'Finding another professional',
        'request_unassigned' => 'Request needs reassignment',
        'request_status' => 'Request updated',
        'quotation_received' => 'Quotation received',
        'quotation_accepted' => 'Quotation accepted',
        'quotation_rejected' => 'Quotation declined',
        'payment_received' => 'Payment received',
        'payment_submitted' => 'Payment submitted',
        'payment_confirmed' => 'Payment confirmed',
        'whish_payment_approved' => 'WHISH payment approved',
        'whish_payment_rejected' => 'WHISH payment needs attention',
        'complaint_update' => 'Complaint update',
        'complaint_update_worker' => 'Complaint update',
        'review_requested' => 'How did it go?',
        'reschedule_requested' => 'Reschedule requested',
        'reschedule_accepted' => 'Reschedule accepted',
        'reschedule_declined' => 'Reschedule declined',
        'reschedule_superseded' => 'Reschedule request no longer needed',
    ],

    // Bodies, rendered at display time with parameters from the stored `data`.
    'messages' => [
        'request_submitted' => 'Your request :reference has been submitted.',
        'request_assigned' => 'Request :reference has been assigned to you. Review it, then accept or decline.',
        'professional_assigned' => 'A professional has been assigned to request :reference and will confirm shortly.',
        'professional_declined' => 'We are assigning another professional to request :reference.',
        'request_unassigned' => 'A professional declined request :reference. Please reassign it.',
        'request_status' => 'Request :reference is now “:status_label”.',
        'quotation_received' => 'You received a quotation of :amount for request :reference.',
        'quotation_accepted' => 'Your quotation of :amount for request :reference was accepted.',
        'quotation_rejected' => 'Your quotation for request :reference was declined.',
        'payment_received' => 'Payment was received for request :reference.',
        'payment_submitted' => 'Your WHISH payment for request :reference was submitted and is awaiting verification.',
        'payment_confirmed' => 'Payment for request :reference has been confirmed.',
        'whish_payment_approved' => 'Your WHISH payment of :amount for request :reference was approved.',
        'whish_payment_rejected' => 'Your WHISH payment for request :reference could not be verified. Please check the details and submit again.',
        'complaint_update' => 'Your complaint about request :reference is now “:complaint_status_label”.',
        'complaint_update_worker' => 'A complaint about request :reference is now “:complaint_status_label”.',
        'review_requested' => 'Please leave a review for request :reference.',
        'reschedule_requested' => 'The customer proposed a new time for request :reference. Review it, then accept or decline.',
        'reschedule_accepted' => 'Your proposed new time for request :reference was accepted.',
        'reschedule_declined' => 'Your proposed new time for request :reference was declined.',
        'reschedule_superseded' => 'Your reschedule request for :reference is no longer needed — the appointment was already updated.',
    ],
];
