@props([
    'type' => 'request',   // request | quote | payment | verification | complaint
    'value',
])
@php
    // Visual tone per status value. Labels live in lang/{locale}/status.php.
    $tones = [
        'request' => [
            'pending' => 'warning', 'awaiting_acceptance' => 'warning',
            'accepted' => 'info', 'scheduled' => 'info', 'on_the_way' => 'info', 'in_progress' => 'info',
            'completed' => 'success', 'cancelled' => 'danger', 'rejected' => 'danger',
        ],
        'quote' => ['pending' => 'warning', 'accepted' => 'success', 'rejected' => 'danger', 'expired' => 'neutral'],
        'payment' => [
            'pending' => 'warning', 'paid' => 'success', 'failed' => 'danger',
            'refunded' => 'neutral', 'partially_refunded' => 'neutral',
        ],
        'verification' => ['pending' => 'warning', 'verified' => 'success', 'rejected' => 'danger', 'suspended' => 'danger'],
        'complaint' => ['open' => 'warning', 'investigating' => 'info', 'resolved' => 'success', 'closed' => 'neutral'],
    ];
    $tone = $tones[$type][$value] ?? 'neutral';
    $label = __("status.$type.$value");
    if (str_starts_with($label, 'status.')) {
        $label = ucfirst(str_replace('_', ' ', (string) $value));
    }
@endphp
<x-badge :tone="$tone" dot :attributes="$attributes">{{ $label }}</x-badge>
