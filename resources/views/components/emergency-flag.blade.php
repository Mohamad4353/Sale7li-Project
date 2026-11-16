@props([
    'variant' => 'compact',   // compact — inline pill for lists | banner — detail pages
    'label' => null,
    'note' => null,
])
@php($label = $label ?? __('requests.emergency'))
@if ($variant === 'banner')
    <div role="note" {{ $attributes->merge(['class' => 'flex items-start gap-3 rounded-2xl border border-rose-300 bg-rose-50 p-4']) }}>
        <x-icon name="flag" class="mt-0.5 h-5 w-5 shrink-0 text-rose-600" />
        <div>
            <p class="font-extrabold text-rose-700">{{ $label }}</p>
            <p class="mt-0.5 text-sm text-rose-700/90">{{ $note ?? __('requests.emergency_note') }}</p>
        </div>
    </div>
@else
    <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-full bg-rose-100 px-2.5 py-1 text-[11px] font-extrabold text-rose-700']) }}>
        <x-icon name="flag" class="h-3 w-3" />{{ $label }}
    </span>
@endif
