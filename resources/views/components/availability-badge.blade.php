@props([
    'profile' => null,
    'busy' => null,        // pass a bool when the caller knows job state
])
@php
    $status = $profile?->availabilityStatus($busy) ?? 'unavailable';
    $text = [
        'available_today' => 'text-emerald-700',
        'available' => 'text-emerald-700',
        'busy' => 'text-amber-700',
        'unavailable' => 'text-faint',
    ][$status];
    $dot = [
        'available_today' => 'bg-emerald-500',
        'available' => 'bg-emerald-400',
        'busy' => 'bg-amber-500',
        'unavailable' => 'bg-line-strong',
    ][$status];
@endphp
<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 text-xs font-bold $text"]) }}>
    <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $dot }}" aria-hidden="true"></span>
    {{ __('workers.availability_status.'.$status) }}
</span>
