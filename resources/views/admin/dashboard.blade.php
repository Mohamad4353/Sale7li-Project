@extends('layouts.app')
@section('content')
@php($title = __('nav.dashboard'))
@php($money = fn ($v) => '$'.number_format((float) $v, 0))

<div class="container-app py-8 sm:py-10">
    <x-page-header :eyebrow="__('dashboard.admin_portal')" :title="__('nav.dashboard')" :subtitle="__('dashboard.admin_sub')" />

    {{-- 2 — Primary KPIs --}}
    <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat :label="__('dashboard.stat_users')" :value="$stats['users']" />
        <x-stat :label="__('dashboard.stat_customers')" :value="$stats['customers']" />
        <x-stat :label="__('dashboard.stat_workers')" :value="$stats['workers']" />
        <x-stat :label="__('dashboard.stat_revenue')" :value="'$'.number_format($stats['revenue'], 0)" />
    </div>

    {{-- 3 — Operational health --}}
    <section class="mt-10">
        <h2 class="t-section">{{ __('dashboard.operations_heading') }}</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-stat :label="__('dashboard.stat_active_requests')" :value="$stats['active_requests']" />
            <x-stat :label="__('dashboard.stat_completed_requests')" :value="$stats['completed_requests']" />
            <x-stat :label="__('dashboard.stat_cancelled_requests')" :value="$stats['cancelled_requests']" />
            <x-stat :label="__('dashboard.stat_complaints_open')" :value="$stats['complaints_open']" />
            <x-stat :label="__('dashboard.stat_payments_completed')" :value="$stats['payments_completed']" />
            <x-stat :label="__('dashboard.stat_payments_pending')" :value="$stats['payments_pending']" />
            <x-stat :label="__('dashboard.stat_refunds')" :value="$stats['refunds']" />
            {{-- WHISH review queue: a KPI that is also the link into the queue,
                 flagged when work is waiting. --}}
            <a href="{{ route('admin.payments.whish') }}"
               class="group rounded-2xl border p-5 shadow-card transition hover:border-line-strong {{ $stats['whish_pending'] > 0 ? 'border-brand bg-brand/10' : 'border-line bg-surface' }}">
                <div class="flex items-center justify-between gap-2">
                    <span class="t-small">{{ __('dashboard.stat_whish_pending') }}</span>
                    <x-icon name="arrow-right" class="h-3.5 w-3.5 shrink-0 text-muted transition group-hover:text-ink" />
                </div>
                <div class="mt-2 t-num">{{ $stats['whish_pending'] }}</div>
            </a>
        </div>
    </section>

    {{-- 4–7 — Analytics (presentation only; Phase 29 calculations unchanged) --}}
    <section class="mt-10">
        <h2 class="t-section">{{ __('dashboard.analytics_heading') }}</h2>

        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-stat :label="__('dashboard.stat_verified_workers')" :value="$kpis['verified_workers']" />
            <x-stat :label="__('dashboard.stat_completion_rate')" :value="$kpis['completion_rate'].'%'" />
            <x-stat :label="__('dashboard.stat_cancellation_rate')" :value="$kpis['cancellation_rate'].'%'" />
            <x-stat :label="__('dashboard.stat_avg_requests_day')" :value="$kpis['avg_requests_per_day']" />
        </div>

        <div class="mt-6 grid gap-5 lg:grid-cols-2">
            <x-chart-line :series="$requestsPerDay" :title="__('dashboard.chart_requests_30d')" data-chart="admin-requests-30d" />
            <x-chart-bars :series="$requestsByStatus" :title="__('dashboard.chart_requests_by_status')" data-chart="admin-requests-by-status" />
            <x-chart-line :series="$revenuePerDay" :title="__('dashboard.chart_revenue_30d')" :format-value="$money" data-chart="admin-revenue-30d" />
            <x-chart-bars :series="$requestsByCategory" :title="__('dashboard.chart_by_category')" data-chart="admin-requests-by-category" />
            <x-chart-line :series="$newCustomersPerDay" :title="__('dashboard.chart_new_customers_30d')" data-chart="admin-new-customers-30d" />
            <x-chart-bars :series="$workerVerification" :title="__('dashboard.chart_worker_verification')" data-chart="admin-worker-verification" />
        </div>
    </section>

    {{-- 8 — Operational lists + actions --}}
    <div class="mt-10 grid gap-6 lg:grid-cols-[1.5fr_1fr]">
        <x-card padding="p-0">
            <div class="flex items-center justify-between px-6 pt-6">
                <h2 class="t-section">{{ __('dashboard.recent_requests') }}</h2>
                <a href="{{ route('admin.requests.index') }}" class="inline-flex items-center gap-1 text-[13px] font-bold text-muted transition hover:text-ink">
                    {{ __('messages.manage') }}<x-icon name="arrow-right" class="h-3.5 w-3.5" />
                </a>
            </div>
            <div class="mt-3 divide-y divide-line">
                @forelse ($requests as $r)
                    <div class="flex items-center justify-between gap-3 px-6 py-4">
                        <div class="min-w-0">
                            <div class="truncate font-bold">{{ $r->category->localized_name }} · {{ $r->customer->name }}</div>
                            <div class="t-small">{{ $r->reference }}</div>
                        </div>
                        <x-status type="request" :value="$r->worker_id && $r->status === 'pending' ? 'awaiting_acceptance' : $r->status" />
                    </div>
                @empty
                    <x-empty icon="clipboard" :title="__('admin.no_requests')" />
                @endforelse
            </div>
        </x-card>

        <x-card>
            <h2 class="t-section">{{ __('admin.pending_verification') }}</h2>
            <div class="mt-4 space-y-4">
                @forelse ($pendingWorkers as $p)
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <div class="truncate font-bold">{{ $p->user->name }}</div>
                            <div class="truncate t-small">{{ $p->user->email }}</div>
                        </div>
                        <form method="POST" action="{{ route('admin.workers.verification', $p) }}">
                            @csrf
                            <label class="sr-only" for="v-{{ $p->id }}">{{ __('workers.verification_status') }}</label>
                            <select id="v-{{ $p->id }}" name="verification_status" data-autosubmit class="input py-2">
                                <option value="pending" @selected($p->verification_status === 'pending')>{{ __('admin.keep_pending') }}</option>
                                <option value="verified" @selected($p->verification_status === 'verified')>{{ __('admin.verify') }}</option>
                                <option value="rejected" @selected($p->verification_status === 'rejected')>{{ __('admin.reject') }}</option>
                            </select>
                        </form>
                    </div>
                @empty
                    <p class="t-muted">{{ __('admin.no_pending') }}</p>
                @endforelse
            </div>
        </x-card>
    </div>

    <div class="mt-6 flex flex-wrap gap-3">
        <x-button :href="route('admin.users.index')" variant="secondary" size="sm">{{ __('nav.users') }}</x-button>
        <x-button :href="route('admin.services.index')" variant="secondary" size="sm">{{ __('nav.services') }}</x-button>
        <x-button :href="route('admin.requests.index')" variant="secondary" size="sm">{{ __('nav.requests') }}</x-button>
    </div>
</div>
@endsection
