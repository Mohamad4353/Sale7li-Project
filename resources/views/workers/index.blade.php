@extends('layouts.app')
@section('content')
@php($title = __('workers.discover_title'))
@php($activeCount = ($filters->category ? 1 : 0) + ($filters->minRating ? 1 : 0) + ($filters->minPrice !== null || $filters->maxPrice !== null ? 1 : 0))

@push('head')
    <link rel="canonical" href="{{ $seo['canonical'] }}">
    @if ($seo['prev'])<link rel="prev" href="{{ $seo['prev'] }}">@endif
    @if ($seo['next'])<link rel="next" href="{{ $seo['next'] }}">@endif
@endpush

<div class="container-x py-8 sm:py-12">
    {{-- Always server-rendered: page heading + intro (SEO). --}}
    <div class="max-w-2xl">
        <p class="t-eyebrow text-amber-600">{{ __('services.eyebrow') }}</p>
        <h1 class="mt-2 t-page">{{ __('workers.discover_title') }}</h1>
        <p class="mt-3 t-muted">{{ __('workers.discover_sub') }}</p>
    </div>

    {{--
        The interactive marketplace. With JS, <MarketplaceSearch> mounts here and
        replaces the slot. Without JS (or before mount) the complete Blade
        marketplace inside the slot is fully functional: search, category, sort,
        minimum rating, price range, pagination — all server-side, crawlable.
    --}}
    <x-react root="MarketplaceSearch" :props="$reactProps" class="mt-8 block">
        <form method="GET" action="{{ route('workers.index') }}" data-no-busy>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <div class="relative flex-1">
                    <label for="wk-q" class="sr-only">{{ __('services.search') }}</label>
                    <input id="wk-q" name="q" value="{{ $filters->q }}" class="input pe-11" placeholder="{{ __('workers.discover_title') }}…">
                    <button type="submit" class="absolute inset-y-0 end-2 grid place-items-center text-muted" aria-label="{{ __('services.search') }}">
                        <x-icon name="arrow-right" class="h-4 w-4" />
                    </button>
                </div>
                <label for="wk-sort" class="sr-only">{{ __('services.sort_label') }}</label>
                <select id="wk-sort" name="sort" data-autosubmit class="input sm:w-56">
                    @foreach ($filters->sortOptions as $opt)
                        <option value="{{ $opt }}" @selected($filters->sort === $opt)>{{ __('workers.sort.'.$opt) }}</option>
                    @endforeach
                </select>
            </div>

            @if ($filters->isActive())
                <div class="mt-4 flex flex-wrap items-center gap-2">
                    @if ($filters->hasSearch())
                        <x-badge tone="neutral">{{ __('services.active_search', ['term' => $filters->q]) }}</x-badge>
                    @endif
                    @if ($filters->category)
                        @php($cat = $categories->firstWhere('slug', $filters->category))
                        <x-badge tone="neutral">{{ __('services.active_category', ['name' => $cat?->localized_name ?? $filters->category]) }}</x-badge>
                    @endif
                    @if ($filters->minRating)
                        <x-badge tone="neutral">{{ __('workers.rating_stars', ['n' => $filters->minRating]) }}</x-badge>
                    @endif
                    @if ($filters->minPrice !== null || $filters->maxPrice !== null)
                        <x-badge tone="neutral">{{ __('services.price') }}: {{ $filters->minPrice ?? '0' }}–{{ $filters->maxPrice ?? '∞' }}</x-badge>
                    @endif
                    <a href="{{ route('workers.index') }}" class="text-[13px] font-bold text-muted transition hover:text-ink">{{ __('services.clear_filters') }}</a>
                </div>
            @endif

            <div class="mt-6 grid gap-6 lg:grid-cols-[240px_1fr] lg:gap-8">
                <x-filter-drawer :active="$activeCount">
                    @include('partials.marketplace-filters', ['showRating' => true, 'showPrice' => true])
                </x-filter-drawer>

                <div>
                    <p class="t-small">{{ trans_choice('services.results_count', $workers->total(), ['count' => $workers->total()]) }}</p>

                    <div class="mt-3 grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                        @forelse ($workers as $worker)
                            <x-worker-card :worker="$worker" />
                        @empty
                            <div class="sm:col-span-2 xl:col-span-3">
                                <x-card padding="p-0">
                                    <x-empty icon="users"
                                        :title="$filters->isActive() ? __('workers.none_title') : __('workers.none_yet_title')"
                                        :message="$filters->isActive() ? __('workers.none_hint') : __('workers.none_yet_hint')">
                                        @if ($filters->isActive())
                                            <x-slot:action>
                                                <x-button :href="route('workers.index')" variant="secondary" size="sm">{{ __('services.clear_filters') }}</x-button>
                                            </x-slot:action>
                                        @endif
                                    </x-empty>
                                </x-card>
                            </div>
                        @endforelse
                    </div>

                    <div class="mt-8">{{ $workers->links() }}</div>
                </div>
            </div>
        </form>
    </x-react>
</div>
@endsection
