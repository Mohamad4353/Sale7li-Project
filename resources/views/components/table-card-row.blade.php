@props(['label'])
{{-- A label/value pair inside <x-table-card>'s `rows` slot. --}}
<div class="flex items-baseline justify-between gap-3">
    <dt class="t-small shrink-0">{{ $label }}</dt>
    <dd class="min-w-0 text-end text-sm font-semibold tabular-nums">{{ $slot }}</dd>
</div>
