<?php

// Advisory AI Service Assistant on the booking page. Never authoritative — it
// only ever pre-fills the existing category choice and problem description.
return [
    'title' => 'Need help choosing a service?',
    'prompt' => 'Describe the problem in your own words and get a suggested service and a clearer description.',
    'textarea_label' => 'Describe your problem',
    'textarea_placeholder' => 'Example: my AC is running but the room isn\'t getting cold.',
    'action' => 'Get AI suggestion',
    'loading' => 'Getting a suggestion…',
    'suggestion_badge' => 'AI suggestion',
    'suggestion_heading' => 'Review before continuing',
    'suggested_service' => 'Suggested service',
    'disclaimer' => 'This is only a suggestion, not a booking decision — review and edit it before continuing.',
    'use_suggestion' => 'Use suggestion',
    'dismiss' => 'Dismiss',
    'unavailable' => 'AI assistance is currently unavailable. You can continue booking manually.',
    'error' => 'Something went wrong getting a suggestion. You can continue booking manually.',
];
