@props([
    'route',            // route name the pills link to
    'current' => null,  // active filter key (active|completed|cancelled) or null for "All"
    'ns' => 'requests', // translation namespace for the labels (requests | workers)
])
@php
    $pills = [
        null => __($ns.'.filter_all'),
        'active' => __($ns.'.filter_active'),
        'completed' => __($ns.'.filter_completed'),
        'cancelled' => __($ns.'.filter_cancelled'),
    ];
@endphp
<nav {{ $attributes->merge(['class' => 'flex flex-wrap gap-2']) }} aria-label="{{ __($ns.'.filter_label') }}">
    @foreach ($pills as $key => $label)
        <a href="{{ route($route, $key ? ['status' => $key] : []) }}"
           @class([
               'inline-flex items-center rounded-xl border px-3 py-2 text-[13px] font-bold transition',
               'border-ink bg-ink text-white' => $current === $key,
               'border-line bg-surface text-muted hover:text-ink' => $current !== $key,
           ])
           @if ($current === $key) aria-current="page" @endif>
            {{ $label }}
        </a>
    @endforeach
</nav>
