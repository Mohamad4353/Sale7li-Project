@extends('layouts.app')
@section('content')
@php($title = __('workers.jobs_title'))

<div class="container-app py-8 sm:py-10">
    <x-page-header :eyebrow="__('workers.eyebrow')" :title="__('workers.jobs_title')" :subtitle="__('workers.jobs_sub')" />

    <x-request-status-filter route="worker.jobs.index" ns="workers" :current="$status" class="mt-7" />

    <div class="mt-5 space-y-4">
        @forelse ($requests as $r)
            <a href="{{ route('worker.jobs.show', $r) }}" @class([
                'block rounded-2xl border bg-surface p-5 shadow-card transition hover:shadow-raised sm:p-6',
                'border-line hover:border-line-strong' => ! $r->is_emergency,
                'border-rose-300 ring-1 ring-rose-200' => $r->is_emergency,
            ])>
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <div class="flex items-center gap-3">
                            <span class="text-xl">{{ $r->category->icon }}</span>
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="truncate font-extrabold">{{ $r->category->localized_name }}</span>
                                    @if ($r->is_emergency)
                                        <x-emergency-flag />
                                    @endif
                                </div>
                                <div class="t-small">{{ $r->reference }} · {{ $r->customer->name }}</div>
                            </div>
                        </div>
                        <p class="mt-3 line-clamp-2 t-muted">{{ $r->problem_description }}</p>
                        @if ($r->is_emergency)<p class="mt-2 text-xs font-semibold text-rose-600">{{ __('workers.emergency_hint') }}</p>@endif
                    </div>
                    <x-status type="request" :value="$r->status === 'pending' ? 'awaiting_acceptance' : $r->status" class="shrink-0" />
                </div>
            </a>
        @empty
            <x-card padding="p-0">
                <x-empty icon="clipboard" :title="__('workers.no_jobs_title')" :message="__('workers.no_jobs_hint')" />
            </x-card>
        @endforelse
    </div>

    <div class="mt-5">{{ $requests->links() }}</div>
</div>
@endsection
