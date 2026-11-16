@props(['tone' => 'info', 'dismissible' => false, 'title' => null])
@php
    $tones = [
        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
        'danger' => 'border-rose-200 bg-rose-50 text-rose-800',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-900',
        'info' => 'border-sky-200 bg-sky-50 text-sky-800',
    ];
    $t = $tones[$tone] ?? $tones['info'];
@endphp
<div
    @if ($dismissible) x-data="{ show: true }" x-show="show" x-transition @endif
    role="{{ $tone === 'danger' ? 'alert' : 'status' }}"
    {{ $attributes->merge(['class' => "flex items-start gap-3 rounded-xl border px-4 py-3 text-sm $t"]) }}>
    <div class="min-w-0 flex-1">
        @if ($title)<p class="font-bold">{{ $title }}</p>@endif
        <div class="{{ $title ? 'mt-0.5' : '' }} font-semibold">{{ $slot }}</div>
    </div>
    @if ($dismissible)
        <button type="button" @click="show = false" class="-me-1 shrink-0 rounded-lg p-1 opacity-60 transition hover:opacity-100" aria-label="{{ __('messages.dismiss') }}">
            <x-icon name="close" class="h-4 w-4" />
        </button>
    @endif
</div>
