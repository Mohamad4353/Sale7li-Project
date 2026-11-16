<?php

return [
    // Human-readable labels for the ActivityLog action codes written by
    // App\Services\RequestWorkflow, shown in the admin request activity feed.
    'request.created' => 'تم إنشاء الطلب',
    'request.assigned' => 'تم تعيين مختص',
    'request.declined' => 'رفض المختص تنفيذ العمل',
    'request.status_changed' => 'تغيّرت الحالة',
    'request.reschedule_requested' => 'اقترح العميل موعدًا جديدًا',
    'request.reschedule_accepted' => 'وافق المختص على الموعد المقترح',
    'request.reschedule_declined' => 'رفض المختص الموعد المقترح',
    'request.reschedule_superseded' => 'أصبح اقتراح تغيير الموعد غير ساري بعد تحديد موعد جديد',

    // Used when a status-change entry carries its target state in metadata.
    'status_changed_to' => 'تغيّرت الحالة إلى :status',
];
