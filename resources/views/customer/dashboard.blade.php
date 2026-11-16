@extends('layouts.app')
@section('content')
@php($title = __('nav.dashboard'))
@php($activeRequests = $requests->whereIn('status', ['accepted', 'scheduled', 'on_the_way', 'in_progress']))

<div class="container-app py-8 sm:py-10">
    <x-page-header
        :eyebrow="__('dashboard.customer_portal')"
        :title="__('dashboard.greeting', ['name' => auth()->user()->name])" />

    {{-- 2 — Primary action --}}
    <x-card class="mt-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <p class="t-card">{{ __('dashboard.book_service') }}</p>
            <p class="mt-1 t-muted">{{ __('dashboard.customer_sub') }}</p>
        </div>
        <x-button :href="route('customer.booking.create')" variant="accent" class="shrink-0">
            <x-icon name="plus" class="h-4 w-4" />{{ __('dashboard.book_service') }}
        </x-button>
    </x-card>

    {{-- 3 — Active requests: a live view of every service currently in flight,
         with its progress, professional, next appointment and quick actions.
         Reuses the already-eager-loaded $requests collection (no extra query). --}}
    @if ($activeRequests->isNotEmpty())
        <x-card padding="p-0" class="mt-6">
            <h2 class="t-section px-6 pt-6">{{ __('dashboard.stat_active_requests') }}</h2>
            <ul class="mt-3 divide-y divide-line">
                @foreach ($activeRequests as $r)
                    @php($hintKey = $r->worker_id && $r->status === 'pending' ? 'assigned' : $r->status)
                    <li class="px-6 py-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-surface-2 text-lg">{{ $r->category->icon ?: '🛠️' }}</span>
                                <div class="min-w-0">
                                    <div class="truncate font-bold">{{ $r->category->localized_name }}</div>
                                    <div class="t-small tabular-nums">{{ $r->reference }}</div>
                                </div>
                            </div>
                            <x-status type="request" :value="$r->worker_id && $r->status === 'pending' ? 'awaiting_acceptance' : $r->status" />
                        </div>

                        <p class="mt-3 t-small">{{ __('requests.progress_hint.'.$hintKey) }}</p>

                        @if ($r->worker || $r->appointment?->scheduled_date)
                            <div class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-2 t-small">
                                @if ($r->worker)
                                    <span class="flex items-center gap-2">
                                        <x-avatar :name="$r->worker->name" size="sm" />
                                        {{ $r->worker->name }}
                                    </span>
                                @endif
                                @if ($r->appointment?->scheduled_date)
                                    <span class="flex items-center gap-1.5">
                                        <x-icon name="calendar" class="h-4 w-4 shrink-0" />
                                        {{ $r->appointment->scheduled_date->translatedFormat('M j') }}
                                        · {{ \Illuminate\Support\Str::of((string) $r->appointment->scheduled_time)->substr(0, 5) }}
                                    </span>
                                @endif
                            </div>
                        @endif

                        @if ($r->payment_status !== 'paid' && $r->quoted_total)
                            <p class="mt-2 t-small font-semibold text-ink">
                                {{ __('payments.amount_due') }}: <x-money :amount="$r->quoted_total" />
                            </p>
                        @endif

                        <div class="mt-4 flex flex-wrap gap-2">
                            <x-button :href="route('customer.requests.show', $r)" variant="secondary" size="sm">{{ __('dashboard.view_request') }}</x-button>
                            @if ($r->worker)
                                <x-button :href="route('customer.messages.show', $r)" variant="secondary" size="sm">
                                    <x-icon name="chat" class="h-4 w-4" />{{ __('requests.message') }}
                                </x-button>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        </x-card>
    @endif

    {{-- 4 — Request status summary --}}
    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat :label="__('dashboard.stat_total_requests')" :value="$stats['total']" />
        <x-stat :label="__('dashboard.stat_pending')" :value="$stats['pending']" />
        <x-stat :label="__('dashboard.stat_active')" :value="$stats['active']" />
        <x-stat :label="__('dashboard.stat_completed')" :value="$stats['completed']" />
    </div>

    {{-- 5 — Recent requests + discovery --}}
    <div class="mt-10 grid gap-6 lg:grid-cols-[1.5fr_1fr]">
        <x-card padding="p-0">
            <div class="flex items-center justify-between px-6 pt-6">
                <h2 class="t-section">{{ __('dashboard.recent_requests') }}</h2>
                <a href="{{ route('customer.requests.index') }}" class="inline-flex items-center gap-1 text-[13px] font-bold text-muted transition hover:text-ink">
                    {{ __('messages.view_all') }}<x-icon name="arrow-right" class="h-3.5 w-3.5" />
                </a>
            </div>
            <div class="mt-3 divide-y divide-line">
                @forelse ($requests as $r)
                    <a href="{{ route('customer.requests.show', $r) }}" class="flex items-center justify-between gap-3 px-6 py-4 transition hover:bg-surface-2">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-surface-2 text-lg">{{ $r->category->icon ?: '🛠️' }}</span>
                            <div class="min-w-0">
                                <div class="truncate font-bold">{{ $r->category->localized_name }}</div>
                                <div class="t-small">{{ $r->reference }} · <x-date :value="$r->created_at" /></div>
                            </div>
                        </div>
                        <x-status type="request" :value="$r->worker_id && $r->status === 'pending' ? 'awaiting_acceptance' : $r->status" />
                    </a>
                @empty
                    <x-empty icon="clipboard" :title="__('dashboard.no_requests')" :message="__('dashboard.no_requests_hint')">
                        <x-slot:action>
                            <x-button :href="route('customer.booking.create')" variant="accent" size="sm">{{ __('dashboard.book_service') }}</x-button>
                        </x-slot:action>
                    </x-empty>
                @endforelse
            </div>
        </x-card>

        <section>
            <h2 class="t-section">{{ __('dashboard.recommended_services') }}</h2>
            <div class="mt-4 grid gap-3">
                @foreach ($categories as $c)
                    <a href="{{ route('services.show', $c) }}" class="flex items-center gap-4 rounded-2xl border border-line bg-surface p-4 shadow-card transition hover:border-line-strong hover:shadow-raised">
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-surface-2 text-lg">{{ $c->icon ?: '🛠️' }}</span>
                        <div class="min-w-0">
                            <div class="truncate font-bold">{{ $c->localized_name }}</div>
                            <div class="t-small">{{ __('bookings.from') }} <x-money :amount="$c->base_price" /></div>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    </div>

    {{-- 6 — Your request analytics (presentation only; Phase 29 calculations unchanged) --}}
    <section class="mt-10">
        <h2 class="t-section">{{ __('dashboard.analytics_heading') }}</h2>
        <div class="mt-4 grid gap-5 lg:grid-cols-2">
            <x-chart-bars :series="$requestsByStatus" :title="__('dashboard.chart_my_requests_status')" data-chart="customer-requests-by-status" />
            <x-chart-line :series="$requestsPerDay" :title="__('dashboard.chart_my_requests_30d')" data-chart="customer-requests-30d" />
        </div>
    </section>
</div>
@endsection
