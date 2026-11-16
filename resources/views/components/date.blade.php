@props([
    'value' => null,
    'time' => false,   // include the time of day
    'diff' => false,    // append a relative "(3 days ago)"
])
{{-- One canonical date presentation for the whole app. Locale-aware via
     translatedFormat; timezone and the stored timestamp are never altered. --}}
@php
    $c = $value instanceof \Carbon\CarbonInterface
        ? $value
        : (filled($value) ? \Illuminate\Support\Carbon::parse($value) : null);
    $fmt = $time ? 'M j, Y · H:i' : 'M j, Y';
@endphp
@if ($c)
    <time datetime="{{ $c->toIso8601String() }}" {{ $attributes }}>{{ $c->translatedFormat($fmt) }}@if ($diff) <span class="text-faint">({{ $c->diffForHumans() }})</span>@endif</time>
@else
    <span {{ $attributes }}>{{ __('messages.none') }}</span>
@endif
