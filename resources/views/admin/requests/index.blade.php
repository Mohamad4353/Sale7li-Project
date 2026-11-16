@extends('layouts.app')
@section('content')
@php($title = __('admin.requests_title'))

<div class="container-app py-8 sm:py-10">
    <x-page-header :eyebrow="__('admin.eyebrow')" :title="__('admin.requests_title')" :subtitle="__('admin.requests_sub')">
        <x-slot:actions>
            <x-button :href="route('admin.requests.export', request()->query())" variant="secondary" size="sm">
                {{ __('messages.export_csv') }}
            </x-button>
        </x-slot:actions>
    </x-page-header>

    @php($queues = [
        'all' => ['label' => __('admin.queue_all'), 'count' => null],
        'unassigned' => ['label' => __('admin.queue_unassigned'), 'count' => $counts['unassigned']],
        'emergency' => ['label' => __('admin.queue_emergency'), 'count' => $counts['emergency']],
        'stale' => ['label' => __('admin.queue_stale'), 'count' => $counts['stale']],
    ])
    <div class="mt-7 flex flex-wrap gap-2">
        @foreach ($queues as $key => $meta)
            <a href="{{ route('admin.requests.index', array_filter(['queue' => $key === 'all' ? null : $key, 'status' => $status])) }}"
               @class([
                   'inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-[13px] font-bold transition',
                   'border-ink bg-ink text-white' => $queue === $key,
                   'border-line bg-surface text-muted hover:text-ink' => $queue !== $key,
               ])>
                {{ $meta['label'] }}
                @if (!is_null($meta['count']))
                    <span @class(['rounded-full px-1.5 text-[11px] font-extrabold', 'bg-white/20' => $queue === $key, 'bg-surface-2 text-ink' => $queue !== $key])>{{ $meta['count'] }}</span>
                @endif
            </a>
        @endforeach
    </div>

    <form class="mt-4 flex flex-col gap-3 sm:flex-row" data-no-busy>
        <input type="hidden" name="queue" value="{{ $queue }}">
        <label for="req-status" class="sr-only">{{ __('messages.filter') }}</label>
        <select id="req-status" name="status" class="input max-w-xs">
            <option value="">{{ __('admin.all_statuses') }}</option>
            @foreach (['pending', 'accepted', 'scheduled', 'on_the_way', 'in_progress', 'completed', 'cancelled', 'rejected'] as $s)
                <option value="{{ $s }}" @selected($status === $s)>{{ __('status.request.'.$s) }}</option>
            @endforeach
        </select>
        <x-button type="submit">{{ __('messages.filter') }}</x-button>
    </form>

    <div class="mt-6 space-y-4">
        @forelse ($requests as $r)
            @php($eligible = $workers->filter(fn ($w) => $w->workerServices->contains('service_category_id', $r->service_category_id)))
            <x-card>
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('admin.requests.show', $r) }}" class="table-action -ms-2.5 font-extrabold text-ink hover:underline">{{ $r->reference }}</a>
                            <span class="text-faint">·</span>
                            <span class="font-bold">{{ $r->category->localized_name }}</span>
                            <x-status type="request" :value="$r->worker_id && $r->status === 'pending' ? 'awaiting_acceptance' : $r->status" />
                            @if ($r->is_emergency)
                                <x-emergency-flag />
                            @endif
                            @if (is_null($r->worker_id) && $r->status === 'pending' && $r->created_at->lte($staleBefore))
                                <x-badge tone="warning" dot>{{ __('admin.stale') }}</x-badge>
                            @endif
                        </div>
                        <div class="mt-2 t-small">
                            {{ __('admin.customer') }}: {{ $r->customer->name }} ·
                            {{ $r->preferred_date?->translatedFormat('M d, Y') }} · {{ $r->preferred_time }}
                            @if ($r->worker) · {{ __('admin.professional') }}: {{ $r->worker->name }}@endif
                        </div>
                        <div class="mt-1 t-small">{{ __('admin.request_age') }}: {{ $r->created_at->diffForHumans() }}</div>
                    </div>

                    <form method="POST" action="{{ route('admin.requests.assign', $r) }}" class="flex flex-col gap-2 sm:flex-row">
                        @csrf
                        <label class="sr-only" for="assign-{{ $r->id }}">{{ __('admin.assign_professional') }}</label>
                        <select id="assign-{{ $r->id }}" name="worker_id" class="input min-w-56" @disabled($eligible->isEmpty())>
                            <option value="">{{ __('admin.assign_professional') }}</option>
                            @forelse ($eligible as $w)
                                <option value="{{ $w->id }}" @selected($r->worker_id === $w->id)>
                                    {{ $w->name }}@if ($w->workerProfile?->rating_count) — ★ {{ number_format($w->workerProfile->rating_average, 1) }}@endif
                                </option>
                            @empty
                                <option value="">{{ __('admin.no_qualified') }}</option>
                            @endforelse
                        </select>
                        <x-button type="submit" variant="secondary" :disabled="$eligible->isEmpty()">
                            {{ $r->worker_id ? __('admin.update_assignment') : __('admin.assign') }}
                        </x-button>
                    </form>
                </div>
            </x-card>
        @empty
            <x-card padding="p-0"><x-empty icon="clipboard" :title="__('admin.no_requests')" /></x-card>
        @endforelse
    </div>

    <div class="mt-5">{{ $requests->links() }}</div>
</div>
@endsection
