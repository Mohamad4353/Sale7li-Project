@props(['padding' => 'p-6'])
<div {{ $attributes->merge(['class' => "rounded-2xl border border-line bg-surface shadow-card $padding"]) }}>
    {{ $slot }}
</div>
