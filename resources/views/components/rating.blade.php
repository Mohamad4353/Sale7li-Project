@props([
    'average' => 0,
    'count' => 0,
    'value' => null,       // an explicit single rating (one review); overrides average/count
    'size' => 'sm',        // sm | md
    'showValue' => true,    // show the numeric + count beside the frame
])
@php
    $single = $value !== null;
    $score = $single ? (float) $value : round((float) $average, 1);
    $filled = max(0, min(5, (int) round($score)));   // 0.x -> 0, never a forced star
    $count = (int) $count;
    $has = $single || $count > 0;
    $textSize = $size === 'md' ? 'text-sm' : 'text-xs';
    $aria = $single
        ? __('reviews.rating_out_of', ['value' => $filled])
        : __('reviews.rating_aria', ['value' => number_format($score, 1), 'count' => $count]);
@endphp
@if ($has)
    <span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 font-bold $textSize"]) }}
          role="img" aria-label="{{ $aria }}">
        <span aria-hidden="true"><span class="text-amber-500">{{ str_repeat('★', $filled) }}</span><span class="text-line">{{ str_repeat('☆', 5 - $filled) }}</span></span>
        @if ($showValue && ! $single)
            <span>{{ number_format($score, 1) }}</span><span class="text-faint">({{ $count }})</span>
        @endif
    </span>
@else
    <span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 font-bold text-muted $textSize"]) }}>
        {{ __('reviews.new_professional') }}
    </span>
@endif
