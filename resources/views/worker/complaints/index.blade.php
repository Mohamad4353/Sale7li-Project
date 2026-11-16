@extends('layouts.app')
@section('content')
@php($title = __('complaints.worker_title'))

<div class="container-app py-8 sm:py-10">
    <x-page-header :eyebrow="__('dashboard.worker_portal')" :title="__('complaints.worker_title')" :subtitle="__('complaints.worker_sub')" />

    <x-card padding="p-0" class="mt-7 overflow-hidden">
        <div class="divide-y divide-line">
            @forelse ($complaints as $complaint)
                <a href="{{ route('worker.complaints.show', $complaint) }}"
                   class="flex flex-col gap-3 p-5 transition hover:bg-surface-2 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <div class="truncate font-bold">{{ __('complaints.categories.'.$complaint->category) }}</div>
                        <div class="mt-0.5 t-small">
                            {{ $complaint->request?->reference ?? __('complaints.admin.no_request') }} ·
                            {{ $complaint->reporter?->name }} ·
                            <x-date :value="$complaint->created_at" />
                        </div>
                    </div>
                    <x-status type="complaint" :value="$complaint->status" class="shrink-0" />
                </a>
            @empty
                <x-empty icon="flag" :title="__('complaints.worker_none')" />
            @endforelse
        </div>
    </x-card>

    <div class="mt-5">{{ $complaints->links() }}</div>
</div>
@endsection
