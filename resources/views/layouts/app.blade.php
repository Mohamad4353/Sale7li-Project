@php
    $isRtl = app()->getLocale() === 'ar';
    $shell = trim($__env->yieldContent('shell')) ?: (auth()->check() ? 'app' : 'marketing');
@endphp
<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ __('marketing.meta_description') }}">
    <title>{{ ($title ?? null) ? $title.' · SALE7LI' : config('app.name', 'SALE7LI') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Noto+Kufi+Arabic:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen bg-paper text-ink">
    <div id="page-progress" aria-hidden="true"></div>
    <a href="#main" class="skip-link">{{ __('messages.skip_to_content') }}</a>

    @if ($shell === 'app' && auth()->check())
        <x-app-nav />
    @else
        <x-marketing-nav />
    @endif

    <x-flash :shell="$shell" />

    <main id="main">{{ $slot ?? '' }}@yield('content')</main>

    @if ($shell !== 'app')
        <footer id="contact" class="mt-24 bg-ink text-white">
            <div class="container-x grid gap-10 py-14 sm:grid-cols-2 lg:grid-cols-[1.5fr_1fr_1fr_1fr]">
                <div>
                    <a href="{{ route('home') }}" class="flex items-center gap-2.5" aria-label="SALE7LI"><span class="brand-mark brand-mark-dark">7</span><span class="text-xl font-extrabold">SALE<span class="text-brand">7</span>LI</span></a>
                    <p class="mt-5 max-w-sm text-sm leading-7 text-white/70">{{ __('marketing.footer_description') }}</p>
                </div>
                <div><h4 class="footer-heading">{{ __('nav.services') }}</h4><div class="mt-4 flex flex-col gap-3 text-sm"><a href="{{ route('services.index') }}" class="footer-link">{{ __('marketing.all_services') }}</a><a href="{{ route('home') }}#how" class="footer-link">{{ __('marketing.how_it_works') }}</a></div></div>
                <div><h4 class="footer-heading">{{ __('marketing.platform') }}</h4><div class="mt-4 flex flex-col gap-3 text-sm"><a href="{{ route('register') }}" class="footer-link">{{ __('marketing.join') }}</a><a href="{{ route('login') }}" class="footer-link">{{ __('nav.login') }}</a></div></div>
                <div><h4 class="footer-heading">{{ __('marketing.support') }}</h4><div class="mt-4 text-sm leading-7 text-white/70">@php($supportEmail = config('sale7li.support_email'))<a href="mailto:{{ $supportEmail }}" class="footer-link">{{ $supportEmail }}</a><br>{{ __('marketing.support_hours') }}</div></div>
            </div>
            <div class="border-t border-white/10"><div class="container-x flex flex-col gap-3 py-5 text-xs text-white/60 sm:flex-row sm:items-center sm:justify-between"><span>© {{ date('Y') }} SALE7LI. {{ __('marketing.rights') }}</span><span>{{ __('marketing.bilingual') }} · {{ __('marketing.secure_platform') }}</span></div></div>
        </footer>
    @else
        <footer class="mt-16 border-t border-line">
            <div class="container-app flex flex-col gap-2 py-6 text-xs text-muted sm:flex-row sm:items-center sm:justify-between">
                <span>© {{ date('Y') }} SALE7LI</span>
                <span class="flex items-center gap-4">
                    <a href="{{ route('support') }}" class="font-bold transition hover:text-ink">{{ __('nav.support') }}</a>
                    <span>{{ __('marketing.secure_platform') }}</span>
                </span>
            </div>
        </footer>
    @endif

    <x-confirm-dialog />

    {{-- Optional per-page scripts (e.g. the React entry via <x-react>). --}}
    @stack('scripts')
</body>
</html>
