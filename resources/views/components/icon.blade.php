@props(['name', 'class' => 'h-4 w-4'])
@php
    // Directional icons mirror automatically under RTL via .rtl-flip.
    $directional = ['arrow-right', 'arrow-left', 'chevron-right', 'chevron-left'];
    $paths = [
        'arrow-right' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'arrow-left' => '<path d="M19 12H5M11 18l-6-6 6-6"/>',
        'chevron-right' => '<path d="M9 6l6 6-6 6"/>',
        'chevron-left' => '<path d="M15 6l-6 6 6 6"/>',
        'check' => '<path d="M20 6 9 17l-5-5"/>',
        'bell' => '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9M10.3 21a1.94 1.94 0 0 0 3.4 0"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'close' => '<path d="M18 6 6 18M6 6l12 12"/>',
        'user' => '<path d="M19 21a7 7 0 0 0-14 0M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/>',
        'home' => '<path d="M3 10.5 12 3l9 7.5M5 9v11h14V9"/>',
        'grid' => '<path d="M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z"/>',
        'clipboard' => '<path d="M9 4h6v3H9zM6 5h2v2h8V5h2a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z"/>',
        'chat' => '<path d="M21 15a2 2 0 0 1-2 2H8l-5 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2Z"/>',
        'wallet' => '<path d="M3 7a2 2 0 0 1 2-2h13v3M3 7v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-6H8a2 2 0 0 1 0-4h13"/>',
        'calendar' => '<path d="M7 3v3M17 3v3M4 8h16M5 6h14a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1Z"/>',
        'star' => '<path d="m12 3 2.9 6 6.1.9-4.5 4.3 1.1 6.1L12 17.8 6.4 20.3l1.1-6.1L3 9.9 9.1 9Z"/>',
        'shield' => '<path d="M12 3 5 6v6c0 5 3 7 7 9 4-2 7-4 7-9V6Z"/>',
        'users' => '<path d="M16 19a5 5 0 0 0-10 0M11 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM19 18a4 4 0 0 0-3-3.9M16 5.1A3 3 0 0 1 16 11"/>',
        'sliders' => '<path d="M4 6h10M18 6h2M4 12h2M10 12h10M4 18h6M14 18h6M14 4v4M6 10v4M10 16v4"/>',
        'flag' => '<path d="M5 21V4M5 4h11l-1.5 4L16 12H5"/>',
        'inbox' => '<path d="M3 13h4l2 3h6l2-3h4M5 5h14l2 8v6a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1v-6Z"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'image' => '<path d="M5 4h14a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1ZM9 10a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM20 16l-4-4-8 8"/>',
        'whatsapp' => '<path d="M3 21l1.65-3.8a9 9 0 1 1 3.4 2.9L3 21ZM9 10a.5.5 0 0 0 1 0v-1a.5.5 0 0 0-1 0v1a5 5 0 0 0 5 5h1a.5.5 0 0 0 0-1h-1a.5.5 0 0 0 0 1"/>',
    ];
    $isDir = in_array($name, $directional, true);
    // Icons are decorative by default. When a caller supplies an aria-label the
    // icon is the sole carrier of that meaning (e.g. the "verified" shield on a
    // compact professional card), so expose it as an image instead of hiding it.
    $labelled = filled($attributes->get('aria-label'));
@endphp
<svg {{ $attributes->merge(['class' => $class . ($isDir ? ' rtl-flip' : '')]) }}
     viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
     stroke-linecap="round" stroke-linejoin="round"
     @if ($labelled) role="img" @else aria-hidden="true" @endif>
    {!! $paths[$name] ?? '' !!}
</svg>
