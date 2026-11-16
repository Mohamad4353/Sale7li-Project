@extends('layouts.app')
@section('content')
@php($title = __('complaints.categories.'.$complaint->category))

<div class="container-app max-w-3xl py-8 sm:py-10">
    <x-page-header
        :back="route('worker.complaints.index')"
        :backLabel="__('complaints.worker_title')"
        :eyebrow="__('dashboard.worker_portal')"
        :title="__('complaints.categories.'.$complaint->category)">
        <x-slot:actions>
            <x-status type="complaint" :value="$complaint->status" />
        </x-slot:actions>
    </x-page-header>

    <div class="mt-8 grid gap-6">
        <x-card>
            <h2 class="t-section">{{ __('complaints.description') }}</h2>
            <p class="mt-2 t-body whitespace-pre-line">{{ $complaint->description }}</p>
            <p class="mt-4 t-small">
                {{ __('complaints.admin.reporter') }}: {{ $complaint->reporter?->name }} ·
                <x-date :value="$complaint->created_at" :time="true" />
            </p>
        </x-card>

        @if ($complaint->request)
            <x-card>
                <h2 class="t-section">{{ __('complaints.admin.related_request') }}</h2>
                <p class="mt-2 t-muted">{{ $complaint->request->reference }} · {{ $complaint->request->category?->localized_name }}</p>
                <x-button :href="route('worker.jobs.show', $complaint->request)" variant="secondary" size="sm" class="mt-3">
                    {{ __('complaints.admin.open_request') }}
                </x-button>
            </x-card>
        @endif
    </div>
</div>
@endsection
