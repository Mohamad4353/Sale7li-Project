@props([
    'root',          // registered component name (see resources/js/react/index.jsx)
    'props' => [],   // JSON-safe, PUBLIC data only — never secrets/prices/tokens
])

{{-- Pull the React entry in once per page, and only on pages that use it. --}}
@once
    @push('scripts')
        @vite('resources/js/react/index.jsx')
    @endpush
@endonce

<div data-react-root="{{ $root }}"
     data-props="{{ json_encode($props, JSON_THROW_ON_ERROR) }}"
     {{ $attributes }}>
    {{-- Server-rendered fallback shown until React mounts (and if JS is off). --}}
    {{ $slot }}
</div>
