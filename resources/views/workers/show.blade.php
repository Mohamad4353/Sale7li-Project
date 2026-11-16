@extends('layouts.app')
@section('content')
@php($title = $worker->name)
@php($p = $worker->workerProfile)
@php($bio = app()->getLocale() === 'ar' ? ($p->bio_ar ?: $p->bio) : $p->bio)
@php($isCustomer = auth()->check() && auth()->user()->role === 'customer')
@php($specialties = $worker->workerServices->pluck('category')->filter())
@php($primaryCategory = $specialties->first())
{{-- The profile CTA carries the professional's primary service into the existing
     booking form (?category is re-validated against the active list server-side;
     no worker id is ever sent — assignment stays an operations decision). --}}
@php($requestHref = $isCustomer
    ? route('customer.booking.create', $primaryCategory ? ['category' => $primaryCategory->slug] : [])
    : route('register'))

<div class="container-app py-8 sm:py-10">
    <x-page-header :back="route('workers.index')" :backLabel="__('workers.profile_back')" :title="$worker->name">
        <x-slot:actions>
            @if ($p->verification_status === 'verified')
                <x-badge tone="success" dot>{{ __('workers.verified') }}</x-badge>
            @endif
        </x-slot:actions>
    </x-page-header>

    {{-- Two columns on desktop. On smaller screens every card flows on its own
         and `order-*` sets the mobile priority: identity, CTA, services,
         availability, work gallery, reviews, then the supporting facts. --}}
    <div class="mt-8 grid gap-6 lg:grid-cols-[1.5fr_1fr] lg:items-start">
        <div class="contents lg:block lg:space-y-6">
            {{-- 1 · Identity --}}
            <x-card class="order-1 lg:order-none">
                <div class="flex items-start gap-4">
                    <x-avatar :name="$worker->name" size="lg"
                              :src="$p->photo ? route('workers.photo', $worker) : null" />
                    <div class="min-w-0">
                        <div class="text-lg font-extrabold">{{ $worker->name }}</div>
                        <x-rating :average="$p->rating_average" :count="$p->rating_count" size="md" class="mt-1" />
                        <x-availability-badge :profile="$p" :busy="$busy ?? null" class="mt-1.5" />
                    </div>
                </div>

                @if ($specialties->isNotEmpty())
                    <div class="mt-4 flex flex-wrap gap-1.5">
                        @foreach ($specialties->take(4) as $category)
                            <x-badge tone="neutral">{{ $category->localized_name }}</x-badge>
                        @endforeach
                        @if ($specialties->count() > 4)
                            <x-badge tone="neutral">+{{ $specialties->count() - 4 }}</x-badge>
                        @endif
                    </div>
                @endif

                <h2 class="mt-6 t-label">{{ __('workers.about') }}</h2>
                <p class="mt-1 t-body whitespace-pre-line">{{ $bio ?: __('workers.no_bio') }}</p>
            </x-card>

            {{-- 3 · Services offered --}}
            <x-card class="order-3 lg:order-none">
                <h2 class="t-section">{{ __('workers.services_offered') }}</h2>
                <div class="mt-4 space-y-2">
                    @forelse ($worker->workerServices as $ws)
                        <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 rounded-xl border border-line px-4 py-3">
                            <a href="{{ route('services.show', $ws->category) }}" class="font-bold transition hover:text-muted">{{ $ws->category->icon }} {{ $ws->category->localized_name }}</a>
                            <div class="flex items-center gap-3">
                                <span class="t-small font-bold text-ink whitespace-nowrap">{{ __('services.from') }} <x-money :amount="$ws->price_from ?? $ws->category->base_price" /></span>
                                @if ($isCustomer)
                                    <x-button :href="route('customer.booking.create', ['category' => $ws->category->slug])" variant="secondary" size="sm">{{ __('services.request_this') }}</x-button>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="t-muted">{{ __('services.none_professionals') }}</p>
                    @endforelse
                </div>
            </x-card>

            {{-- 5 · Work gallery — only visible portfolio items, ordered by the
                 professional's own sort_order (applied in WorkerController@show).
                 Hidden entirely when there is nothing to show. --}}
            @if ($p->portfolioItems->isNotEmpty())
                <x-card class="order-5 lg:order-none">
                    <h2 class="t-section">{{ __('workers.portfolio_gallery_title') }}</h2>
                    <p class="mt-1 t-small">{{ trans_choice('workers.portfolio_gallery_count', $p->portfolioItems->count(), ['count' => $p->portfolioItems->count()]) }}</p>
                    <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
                        @foreach ($p->portfolioItems as $item)
                            @php($imageUrl = route('workers.portfolio.image', [$worker, $item]).'?v='.$item->updated_at?->timestamp)
                            <figure class="overflow-hidden rounded-xl border border-line bg-paper">
                                <a href="{{ $imageUrl }}" target="_blank" rel="noopener noreferrer" class="group block"
                                   aria-label="{{ ($item->title ?: __('workers.portfolio_sample_alt')).' — '.__('workers.portfolio_view_full') }}">
                                    <img src="{{ $imageUrl }}"
                                         alt="{{ $item->title ?: __('workers.portfolio_sample_alt') }}"
                                         width="320" height="320" loading="lazy" decoding="async"
                                         class="aspect-square w-full object-cover transition group-hover:opacity-90">
                                </a>
                                @if ($item->title || $item->description)
                                    <figcaption class="space-y-0.5 p-3">
                                        @if ($item->title)
                                            <span class="line-clamp-2 block break-words font-bold">{{ $item->title }}</span>
                                        @endif
                                        @if ($item->description)
                                            <span class="line-clamp-3 block break-words t-small">{{ $item->description }}</span>
                                        @endif
                                    </figcaption>
                                @endif
                            </figure>
                        @endforeach
                    </div>
                </x-card>
            @endif

            {{-- 6 · Reviews --}}
            <x-card class="order-6 lg:order-none">
                <h2 class="t-section">{{ __('reviews.title') }}</h2>
                <p class="mt-1 t-small">{{ trans_choice('reviews.count', $p->rating_count, ['count' => $p->rating_count]) }}</p>
                <div class="mt-4 space-y-4">
                    @forelse ($reviews as $review)
                        <div class="border-t border-line pt-4 first:border-0 first:pt-0">
                            <div class="flex items-center justify-between">
                                <span class="font-bold">{{ $review->customer_display_name }}</span>
                                <span class="t-small">{{ $review->created_at->translatedFormat('M Y') }}</span>
                            </div>
                            <x-rating :value="$review->rating" size="md" class="mt-1" />
                            @if ($review->comment)<p class="mt-2 t-muted">{{ $review->comment }}</p>@endif
                        </div>
                    @empty
                        <x-empty icon="star" :title="__('reviews.none_title')" :message="__('reviews.none_hint')" />
                    @endforelse
                </div>
                @if ($reviews->hasPages())<div class="mt-5">{{ $reviews->links() }}</div>@endif
            </x-card>
        </div>

        <aside class="contents lg:block lg:space-y-5">
            {{-- 2 · Primary action --}}
            <x-card class="order-2 lg:order-none">
                <h3 class="t-card">{{ __('workers.request_cta') }}</h3>
                <p class="mt-2 t-muted">{{ __('workers.request_cta_hint') }}</p>
                <x-button :href="$requestHref" class="mt-4 w-full">{{ __('workers.request_cta') }}</x-button>
                <x-whatsapp-button
                    :phone="$worker->phone"
                    :context="$primaryCategory?->localized_name"
                    class="mt-2 w-full" />
                <x-favorite-toggle :worker="$worker" class="mt-2" />
            </x-card>

            {{-- 4 · Availability --}}
            <x-card class="order-4 lg:order-none">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="t-card">{{ __('workers.availability') }}</h3>
                    <x-availability-badge :profile="$p" :busy="$busy ?? null" />
                </div>
                <x-worker-availability :availability="$p->availability" class="mt-3" />
            </x-card>

            {{-- 7 · Supporting facts --}}
            <x-card class="order-7 lg:order-none">
                <h3 class="t-card">{{ __('workers.experience') }}</h3>
                <p class="mt-2 t-muted">{{ trans_choice('workers.experience_count', (int) $p->experience_years, ['count' => (int) $p->experience_years]) }}</p>
            </x-card>

            <x-card class="order-8 lg:order-none">
                <h3 class="t-card">{{ __('workers.service_area') }}</h3>
                <p class="mt-2 t-muted">{{ __('workers.service_area_value', ['km' => (int) $p->service_radius_km]) }}</p>
            </x-card>

            @if ($p->completed_jobs > 0)
                <x-card class="order-9 lg:order-none">
                    <h3 class="t-card">{{ __('workers.completed_jobs') }}</h3>
                    <p class="mt-2 t-muted">{{ __('workers.completed_jobs_count', ['count' => $p->completed_jobs]) }}</p>
                </x-card>
            @endif
        </aside>
    </div>
</div>
@endsection
