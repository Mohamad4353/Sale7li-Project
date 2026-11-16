@props(['name' => '', 'size' => 'md', 'src' => null])
@php
    $initials = collect(preg_split('/\s+/', trim($name)))
        ->filter()
        ->map(fn ($p) => mb_substr($p, 0, 1))
        ->take(2)
        ->implode('');
    $sizes = ['sm' => 'h-9 w-9 text-[11px]', 'md' => 'h-11 w-11 text-xs', 'lg' => 'h-16 w-16 text-lg'];
    $dim = $sizes[$size] ?? $sizes['md'];
    // Intrinsic px per size — lets the browser reserve space and skip work for
    // off-screen avatars. No image processing (runtime has no GD/Imagick).
    $px = ['sm' => 36, 'md' => 44, 'lg' => 64][$size] ?? 44;
@endphp
@if ($src)
    <img src="{{ $src }}" alt="" width="{{ $px }}" height="{{ $px }}" loading="lazy" decoding="async"
         {{ $attributes->merge(['class' => 'shrink-0 rounded-full bg-ink object-cover '.$dim]) }}>
@else
    <span {{ $attributes->merge(['class' => 'grid shrink-0 place-items-center rounded-full bg-ink font-extrabold text-white '.$dim]) }}
          aria-hidden="true">
        {{ $initials ?: '?' }}
    </span>
@endif
