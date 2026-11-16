@props(['amount' => null, 'muted' => false])
{{-- Display-only currency formatting via Money::format (string-based, no float).
     A null amount renders an em dash, never a fabricated $0.00. --}}
@php($formatted = \App\Payments\Money::format($amount))
@if ($formatted === null)
    <span {{ $attributes->merge(['class' => 'text-faint']) }}>{{ __('payments.not_available') }}</span>
@else
    <span {{ $attributes->merge(['class' => $muted ? 't-muted' : '']) }}>{{ $formatted }}</span>
@endif
