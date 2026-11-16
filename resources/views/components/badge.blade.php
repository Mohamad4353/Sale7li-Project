@props(['tone' => 'neutral', 'dot' => false])
@php
    $tones = [
        'neutral' => 'bg-slate-100 text-slate-600',
        'success' => 'bg-emerald-100 text-emerald-700',
        'warning' => 'bg-amber-100 text-amber-800',
        'danger' => 'bg-rose-100 text-rose-700',
        'info' => 'bg-sky-100 text-sky-700',
        'brand' => 'bg-brand/20 text-ink',
    ];
    $dots = [
        'neutral' => 'bg-slate-400', 'success' => 'bg-emerald-500', 'warning' => 'bg-amber-500',
        'danger' => 'bg-rose-500', 'info' => 'bg-sky-500', 'brand' => 'bg-brand',
    ];
    $t = $tones[$tone] ?? $tones['neutral'];
@endphp
<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-extrabold $t"]) }}>
    @if ($dot)<span class="status-dot {{ $dots[$tone] ?? $dots['neutral'] }}"></span>@endif
    {{ $slot }}
</span>
