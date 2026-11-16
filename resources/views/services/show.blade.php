@extends('layouts.app')
@section('content')
@php($title = $service->localized_name)
@php($bookHref = auth()->check() && auth()->user()->role === 'customer'
    ? route('customer.booking.create', ['category' => $service->slug])
    : route('register'))

<div class="container-x py-8 sm:py-12">
    <a href="{{ route('services.index') }}" class="inline-flex items-center gap-1.5 text-[13px] font-bold text-muted transition hover:text-ink">
        <x-icon name="arrow-left" class="h-4 w-4" />{{ __('services.all_services') }}
    </a>

    <div class="mt-6 grid gap-8 lg:grid-cols-[1fr_1.2fr]">
        <div class="grid min-h-64 place-items-center rounded-2xl bg-ink text-7xl text-white sm:min-h-72">{{ $service->icon ?: '🛠️' }}</div>
        <div>
            <x-badge tone="brand">{{ __('services.starting_from') }} <x-money :amount="$service->base_price" /></x-badge>
            <h1 class="mt-4 t-display">{{ $service->localized_name }}</h1>
            <p class="mt-4 max-w-2xl text-lg leading-8 text-muted">{{ $service->localized_description }}</p>
            <div class="mt-7 flex flex-wrap gap-3">
                <x-button :href="$bookHref">{{ __('services.request_this') }}<x-icon name="arrow-right" class="h-4 w-4" /></x-button>
                @guest
                    <x-button :href="route('login')" variant="secondary">{{ __('services.sign_in') }}</x-button>
                @endguest
            </div>
        </div>
    </div>

    <div class="mt-14">
        <div class="flex items-end justify-between gap-4">
            <h2 class="t-section">{{ __('services.available_professionals') }}</h2>
            <a href="{{ route('workers.index', ['category' => $service->slug]) }}" class="inline-flex items-center gap-1 text-[13px] font-bold text-muted transition hover:text-ink">
                {{ __('services.browse_all_professionals') }}<x-icon name="arrow-right" class="h-3.5 w-3.5" />
            </a>
        </div>
        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($service->workerServices as $ws)
                @php($worker = $ws->worker)
                @php($p = $worker->workerProfile)
                <a href="{{ route('workers.show', $worker) }}" class="group flex flex-col rounded-2xl border border-line bg-surface p-5 shadow-card transition hover:border-line-strong hover:shadow-raised">
                    <div class="flex items-center gap-3">
                        <x-avatar :name="$worker->name" size="sm"
                                  :src="$p && $p->photo ? route('workers.photo', $worker) : null" />
                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5">
                                <span class="truncate font-extrabold">{{ $worker->name }}</span>
                                @if ($p && $p->verification_status === 'verified')
                                    <x-icon name="shield" class="h-3.5 w-3.5 shrink-0 text-success" aria-label="{{ __('status.verification.verified') }}" />
                                @endif
                            </div>
                            <x-rating :average="$p?->rating_average ?? 0" :count="$p?->rating_count ?? 0" class="mt-0.5" />
                            <x-availability-badge :profile="$p" class="mt-1" />
                        </div>
                    </div>
                    <div class="mt-4 flex items-center justify-between border-t border-line pt-3 t-small">
                        <span>{{ trans_choice('workers.experience_count', (int) ($p?->experience_years ?? 0), ['count' => (int) ($p?->experience_years ?? 0)]) }}</span>
                        <span class="font-bold text-ink">{{ __('services.from') }} <x-money :amount="$ws->price_from ?? $service->base_price" /></span>
                    </div>
                </a>
            @empty
                <div class="sm:col-span-2 lg:col-span-3">
                    <x-card padding="p-0"><x-empty icon="users" :title="__('services.none_professionals')" /></x-card>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
