@extends('layouts.app')
@section('content')
@php($title = __('nav.dashboard'))
@php($checklistMeta = [
    'profile' => ['label' => __('workers.check_profile'), 'route' => 'worker.profile.edit'],
    'photo' => ['label' => __('workers.check_photo'), 'route' => 'worker.profile.edit'],
    'services' => ['label' => __('workers.check_services'), 'route' => 'worker.services.edit'],
    'availability' => ['label' => __('workers.check_availability'), 'route' => 'worker.availability.edit'],
])

<div class="container-app py-8 sm:py-10">
    <x-page-header
        :eyebrow="__('dashboard.worker_portal')"
        :title="__('dashboard.greeting', ['name' => auth()->user()->name])"
        :subtitle="__('dashboard.worker_sub')">
        <x-slot:actions>
            <x-button :href="route('worker.jobs.index')">{{ __('dashboard.view_jobs') }}</x-button>
        </x-slot:actions>
    </x-page-header>

    {{-- 2–4 — Today's work · earnings · workload --}}
    <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat :label="__('dashboard.stat_today')" :value="$stats['today']" />
        <x-stat :label="__('dashboard.stat_earnings')" :value="\App\Payments\Money::format($stats['earnings'] ?? 0)" />
        <x-stat :label="__('dashboard.stat_pending_jobs')" :value="$stats['pending']" />
        <x-stat :label="__('dashboard.stat_completed')" :value="$stats['completed']" />
    </div>

    {{-- 5–6 — Analytics (presentation only; Phase 29 calculations unchanged) --}}
    <section class="mt-10">
        <h2 class="t-section">{{ __('dashboard.analytics_heading') }}</h2>
        <div class="mt-4 grid gap-5 lg:grid-cols-2">
            <x-chart-bars :series="$jobsByStatus" :title="__('dashboard.chart_my_jobs_status')" data-chart="worker-jobs-by-status" />
            <x-chart-line :series="$completedJobsPerDay" :title="__('dashboard.chart_my_completed_30d')" data-chart="worker-completed-30d" />
        </div>
    </section>

    {{-- 7 — Work queue + profile readiness --}}
    <div class="mt-10 grid gap-6 lg:grid-cols-[1.5fr_1fr]">
        <x-card padding="p-0">
            <div class="flex items-center justify-between px-6 pt-6">
                <h2 class="t-section">{{ __('dashboard.recent_jobs') }}</h2>
                <a href="{{ route('worker.jobs.index') }}" class="inline-flex items-center gap-1 text-[13px] font-bold text-muted transition hover:text-ink">
                    {{ __('messages.view_all') }}<x-icon name="arrow-right" class="h-3.5 w-3.5" />
                </a>
            </div>
            <div class="mt-3 divide-y divide-line">
                @forelse ($requests as $r)
                    <a href="{{ route('worker.jobs.show', $r) }}" class="flex items-center justify-between gap-3 px-6 py-4 transition hover:bg-surface-2">
                        <div class="min-w-0">
                            <div class="truncate font-bold">{{ $r->category->localized_name }}</div>
                            <div class="t-small">{{ $r->reference }} · {{ $r->customer->name }}</div>
                        </div>
                        <x-status type="request" :value="$r->status === 'pending' ? 'awaiting_acceptance' : $r->status" />
                    </a>
                @empty
                    <x-empty icon="clipboard" :title="__('dashboard.no_jobs')" :message="__('dashboard.no_jobs_hint')" />
                @endforelse
            </div>
        </x-card>

        <div class="space-y-6">
            <x-card>
                <div class="flex items-center justify-between gap-3">
                    <h2 class="t-section">{{ __('workers.go_live_title') }}</h2>
                    <span class="t-label tabular-nums">{{ $completeness }}%</span>
                </div>

                @if ($completeness >= 100)
                    <p class="mt-3 flex items-center gap-2 t-muted">
                        <span class="grid h-5 w-5 shrink-0 place-items-center rounded-full bg-emerald-100 text-emerald-700">
                            <x-icon name="check" class="h-3 w-3" />
                        </span>
                        {{ __('workers.go_live_ready') }}
                    </p>
                @else
                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-line">
                        <div class="h-full rounded-full bg-brand transition-all" style="width: {{ $completeness }}%"></div>
                    </div>

                    <p class="mt-3 t-muted">
                        {{ $canGoLive ? __('workers.go_live_ready') : __('workers.go_live_pending') }}
                    </p>

                    <ul class="mt-4 space-y-2.5">
                        @foreach ($checklist as $key => $done)
                            <li class="flex items-center gap-3 text-sm">
                                <span @class([
                                    'grid h-5 w-5 shrink-0 place-items-center rounded-full',
                                    'bg-emerald-100 text-emerald-700' => $done,
                                    'border border-line text-faint' => ! $done,
                                ])>
                                    @if ($done)<x-icon name="check" class="h-3 w-3" />@endif
                                </span>
                                <a href="{{ route($checklistMeta[$key]['route']) }}"
                                   class="font-semibold {{ $done ? 'text-muted line-through' : 'text-ink hover:underline' }}">
                                    {{ $checklistMeta[$key]['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>

            <x-card>
                <h2 class="t-section">{{ __('dashboard.verification') }}</h2>
                <div class="mt-3">
                    <x-status type="verification" :value="$profile->verification_status" />
                </div>
                <p class="mt-3 t-muted">{{ __('dashboard.verification_hint') }}</p>
                <x-button :href="route('worker.profile.edit')" variant="secondary" class="mt-5 w-full">{{ __('dashboard.manage_profile') }}</x-button>
            </x-card>
        </div>
    </div>
</div>
@endsection
