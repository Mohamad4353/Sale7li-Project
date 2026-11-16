@php($otherLocale = app()->getLocale() === 'ar' ? 'en' : 'ar')
<header x-data="{ open: false }" @keydown.escape.window="open = false" class="sticky top-0 z-50 border-b border-line bg-paper">
    <div class="container-x flex h-16 items-center justify-between gap-6">
        <a href="{{ route('home') }}" class="flex items-center gap-2.5" aria-label="SALE7LI">
            <span class="brand-mark">7</span>
            <span class="text-[21px] font-extrabold tracking-[-.04em]">SALE<span class="text-brand">7</span>LI</span>
        </a>
        <nav class="hidden items-center gap-7 text-[13px] font-semibold text-muted lg:flex" aria-label="{{ __('nav.primary') }}">
            <a href="{{ route('home') }}" class="nav-link">{{ __('nav.home') }}</a>
            <a href="{{ route('services.index') }}" class="nav-link">{{ __('nav.services') }}</a>
            <a href="{{ route('workers.index') }}" class="nav-link">{{ __('nav.professionals') }}</a>
            <a href="{{ route('home') }}#how" class="nav-link">{{ __('marketing.how_it_works') }}</a>
            <a href="{{ route('home') }}#about" class="nav-link">{{ __('marketing.about') }}</a>
        </nav>
        <div class="hidden items-center gap-2 lg:flex">
            @auth
                <a href="{{ route('dashboard') }}" class="btn-secondary">{{ __('nav.dashboard') }}</a>
            @else
                <a href="{{ route('login') }}" class="px-3 py-2 text-[13px] font-bold text-muted transition hover:text-ink">{{ __('nav.login') }}</a>
                <a href="{{ route('register') }}" class="btn-primary">{{ __('nav.register') }}</a>
            @endauth
            <x-lang-switch />
        </div>
        <div class="flex items-center gap-2 lg:hidden">
            <x-lang-switch />
            <button @click="open = !open" class="icon-button" :aria-expanded="open" aria-label="{{ __('nav.menu') }}">
                <x-icon name="menu" class="h-4 w-4" x-show="!open" />
                <x-icon name="close" class="h-4 w-4" x-show="open" x-cloak />
            </button>
        </div>
    </div>
    <div x-show="open" x-cloak x-transition class="border-t border-line bg-surface lg:hidden">
        <nav class="container-x grid gap-1 py-4 text-sm font-bold" aria-label="{{ __('nav.primary') }}">
            <a @click="open = false" href="{{ route('home') }}" class="mobile-nav-link">{{ __('nav.home') }}</a>
            <a @click="open = false" href="{{ route('services.index') }}" class="mobile-nav-link">{{ __('nav.services') }}</a>
            <a @click="open = false" href="{{ route('workers.index') }}" class="mobile-nav-link">{{ __('nav.professionals') }}</a>
            <a @click="open = false" href="{{ route('home') }}#how" class="mobile-nav-link">{{ __('marketing.how_it_works') }}</a>
            <a @click="open = false" href="{{ route('home') }}#about" class="mobile-nav-link">{{ __('marketing.about') }}</a>
            @auth
                <a href="{{ route('dashboard') }}" class="mobile-nav-link">{{ __('nav.dashboard') }}</a>
            @else
                <a href="{{ route('login') }}" class="mobile-nav-link">{{ __('nav.login') }}</a>
                <a href="{{ route('register') }}" class="mobile-nav-link">{{ __('nav.register') }}</a>
            @endauth
        </nav>
    </div>
</header>
