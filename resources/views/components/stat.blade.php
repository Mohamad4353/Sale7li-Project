@props(['label', 'value', 'hint' => null])
<div {{ $attributes->merge(['class' => 'rounded-2xl border border-line bg-surface p-5 shadow-card']) }}>
    <div class="t-small">{{ $label }}</div>
    <div class="mt-2 t-num">{{ $value }}</div>
    @if ($hint)<div class="mt-1 t-small">{{ $hint }}</div>@endif
</div>
