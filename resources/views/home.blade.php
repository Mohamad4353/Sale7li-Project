@extends('layouts.app')
@section('content')
@php($bookHref = auth()->check() && auth()->user()->role === 'customer' ? route('customer.booking.create') : route('register'))

{{-- Hero --}}
<section class="hero-shell relative overflow-hidden">
    <div class="container-x grid items-center gap-12 py-16 sm:py-20 lg:grid-cols-[1.03fr_.97fr] lg:py-24">
        <div class="max-w-2xl">
            <div class="eyebrow"><span class="pulse-dot"></span>{{ __('marketing.hero_eyebrow') }}</div>
            <h1 class="hero-title">{{ __('marketing.hero_title_lead') }}<br><span>SALE7LI.</span></h1>
            <p class="hero-copy">{{ __('marketing.hero_copy') }}</p>
            <div class="mt-8 flex flex-wrap gap-3">
                <x-button :href="$bookHref" variant="accent" size="lg">
                    {{ __('marketing.cta_find') }}<x-icon name="arrow-right" class="h-4 w-4" />
                </x-button>
                <x-button :href="route('register')" variant="ghost" size="lg">{{ __('marketing.cta_become') }}</x-button>
            </div>
            <div class="mt-8 flex flex-wrap gap-x-7 gap-y-3 text-xs font-semibold text-white/70">
                <span class="inline-flex items-center gap-1.5"><x-icon name="check" class="h-3.5 w-3.5" />{{ __('marketing.trust_verified') }}</span>
                <span class="inline-flex items-center gap-1.5"><x-icon name="check" class="h-3.5 w-3.5" />{{ __('marketing.trust_flow') }}</span>
                <span class="inline-flex items-center gap-1.5"><x-icon name="check" class="h-3.5 w-3.5" />{{ __('marketing.trust_bilingual') }}</span>
            </div>
        </div>
        <div class="relative mx-auto w-full max-w-[480px]">
            <div class="hero-device">
                <div class="device-top"><span class="device-dot"></span><span class="device-dot"></span><span class="device-dot"></span><span class="ms-auto text-[10px] font-bold text-white/40">SALE7LI</span></div>
                <div class="device-body">
                    <p class="mini-label">{{ __('marketing.device_label') }}</p>
                    <h3 class="mt-1 text-lg font-extrabold tracking-tight">{{ __('marketing.device_title') }}</h3>
                    <div class="mt-6 space-y-3.5">
                        @foreach (__('marketing.device_steps') as $i => $step)
                            <div class="flex items-center gap-3">
                                <span class="grid h-6 w-6 place-items-center rounded-full text-[10px] font-extrabold {{ $i < 3 ? 'bg-ink text-white' : 'bg-surface-2 text-faint' }}">
                                    @if ($i < 3)<x-icon name="check" class="h-3 w-3" />@else{{ $i + 1 }}@endif
                                </span>
                                <span class="text-[13px] font-bold {{ $i < 3 ? 'text-ink' : 'text-faint' }}">{{ $step }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Quick categories --}}
<section class="container-x relative z-10 -mt-9">
    <div class="service-rail">
        @foreach ($categories->take(4) as $category)
            <a href="{{ route('services.show', $category) }}" class="service-quick">
                <span class="service-quick-icon">{{ $category->icon ?: '🛠️' }}</span>
                <span><strong>{{ $category->localized_name }}</strong><small>{{ __('marketing.cta_find') }}</small></span>
                <x-icon name="arrow-right" class="ms-auto h-4 w-4 text-faint" />
            </a>
        @endforeach
    </div>
</section>

{{-- How it works --}}
<section id="how" class="container-x py-20">
    <div class="section-head">
        <div>
            <span class="section-kicker">{{ __('marketing.how_kicker') }}</span>
            <h2>{{ __('marketing.how_title') }}</h2>
        </div>
        <p>{{ __('marketing.how_copy') }}</p>
    </div>
    <div class="mt-12 grid gap-5 md:grid-cols-3">
        @foreach ([1, 2, 3] as $n)
            <div class="process-card">
                <span>0{{ $n }}</span>
                <h3>{{ __("marketing.how_step{$n}_title") }}</h3>
                <p>{{ __("marketing.how_step{$n}_copy") }}</p>
                <div class="process-line"></div>
            </div>
        @endforeach
    </div>
</section>

{{-- Services --}}
<section class="bg-surface py-20">
    <div class="container-x">
        <div class="section-head">
            <div>
                <span class="section-kicker">{{ __('marketing.services_kicker') }}</span>
                <h2>{{ __('marketing.services_title') }}</h2>
            </div>
            <x-button :href="route('services.index')" variant="secondary">
                {{ __('marketing.services_view_all') }}<x-icon name="arrow-right" class="h-4 w-4" />
            </x-button>
        </div>
        <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($categories as $category)
                <a href="{{ route('services.show', $category) }}" class="category-card group">
                    <div class="category-icon">{{ $category->icon ?: '🛠️' }}</div>
                    <div class="mt-7 flex items-start justify-between gap-3">
                        <h3>{{ $category->localized_name }}</h3>
                        <span class="category-arrow"><x-icon name="arrow-right" class="h-3.5 w-3.5" /></span>
                    </div>
                    <p>{{ $category->localized_description }}</p>
                    <div class="mt-6 text-xs font-extrabold uppercase tracking-wider text-faint">
                        {{ __('marketing.starting_from') }} <span class="text-ink"><x-money :amount="$category->base_price" /></span>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>

{{-- Professionals (real, verified only) --}}
<section class="container-x py-20">
    <div class="section-head">
        <div>
            <span class="section-kicker">{{ __('marketing.pros_kicker') }}</span>
            <h2>{{ __('marketing.pros_title') }}</h2>
        </div>
    </div>
    @if ($workers->count())
        <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($workers as $worker)
                <a href="{{ route('workers.show', $worker->user) }}" class="pro-card group block">
                    <div class="pro-photo">
                        <x-avatar :name="$worker->user->name" size="lg" />
                        <span class="verified-badge absolute end-4 top-4"><x-icon name="check" class="h-3 w-3" /> {{ __('marketing.trust_verified') }}</span>
                    </div>
                    <div class="p-5">
                        <h3 class="font-extrabold">{{ $worker->user->name }}</h3>
                        <p class="mt-1 text-xs text-faint">{{ $worker->experience_years }} {{ __('marketing.years_experience') }}</p>
                        <x-rating :average="$worker->rating_average ?? 0" :count="$worker->rating_count ?? 0" class="mt-3" />
                    </div>
                </a>
            @endforeach
        </div>
    @else
        <p class="mt-8 max-w-md text-sm leading-7 text-muted">{{ __('marketing.pros_none') }}</p>
    @endif
</section>

{{-- Why --}}
<section id="about" class="container-x pb-20">
    <div class="trust-panel">
        <div class="trust-copy">
            <span class="section-kicker text-brand">{{ __('marketing.why_kicker') }}</span>
            <h2>{{ __('marketing.why_title') }}</h2>
            <p>{{ __('marketing.why_copy') }}</p>
            <ul class="mt-8 grid gap-3">
                @foreach (__('marketing.why_points') as $point)
                    <li class="flex items-start gap-3 rounded-2xl border border-white/15 bg-white/10 p-4 text-sm font-semibold text-white/85">
                        <x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-brand" />{{ $point }}
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="trust-visual">
            <div class="visual-ring"></div>
            <div class="visual-house">⌂</div>
        </div>
    </div>
</section>

{{-- CTA --}}
<section class="container-x pb-20">
    <div class="cta-panel">
        <div>
            <span class="section-kicker">{{ __('marketing.ready_kicker') }}</span>
            <h2>{{ __('marketing.ready_title') }}</h2>
            <p>{{ __('marketing.ready_copy') }}</p>
        </div>
        <x-button :href="$bookHref" variant="primary" size="lg">
            {{ __('marketing.cta_find') }}<x-icon name="arrow-right" class="h-4 w-4" />
        </x-button>
    </div>
</section>
@endsection
