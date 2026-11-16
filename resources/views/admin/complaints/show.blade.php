@extends('layouts.app')
@section('content')
@php($title = __('complaints.admin.title'))
@php($statuses = ['open', 'investigating', 'resolved', 'closed'])

<div class="container-app max-w-3xl py-8 sm:py-10">
    <x-page-header
        :back="route('admin.complaints.index')"
        :backLabel="__('complaints.admin.title')"
        :eyebrow="__('admin.eyebrow')"
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
                {{ __('complaints.admin.reporter') }}: {{ $complaint->reporter?->name }}
                ({{ $complaint->reporter?->email }}) · {{ $complaint->created_at->translatedFormat('M d, Y · H:i') }}
            </p>
        </x-card>

        <x-card>
            <h2 class="t-section">{{ __('complaints.admin.related_request') }}</h2>
            @if ($complaint->request)
                <p class="mt-2 t-muted">
                    {{ $complaint->request->reference }} · {{ $complaint->request->category?->localized_name }}<br>
                    {{ __('admin.customer') }}: {{ $complaint->request->customer?->name }}
                    @if ($complaint->request->worker) · {{ __('admin.professional') }}: {{ $complaint->request->worker->name }}@endif
                </p>
                <x-button :href="route('admin.requests.show', $complaint->request)" variant="secondary" size="sm" class="mt-3">
                    {{ __('complaints.admin.open_request') }}
                </x-button>
            @else
                <p class="mt-2 t-muted">{{ __('complaints.admin.no_request') }}</p>
            @endif
        </x-card>

        <x-card>
            <h2 class="t-section">{{ __('complaints.admin.update_status') }}</h2>
            <x-alert tone="info" class="mt-3">{{ __('complaints.admin.financial_note') }}</x-alert>
            <form method="POST" action="{{ route('admin.complaints.update', $complaint) }}" class="mt-4 grid gap-4">
                @csrf
                @method('PATCH')
                <x-field :label="__('complaints.admin.col_status')" name="status" required>
                    <select id="f-status" name="status" class="input" required>
                        @foreach ($statuses as $s)
                            <option value="{{ $s }}" @selected(old('status', $complaint->status) === $s)>{{ __('status.complaint.'.$s) }}</option>
                        @endforeach
                    </select>
                </x-field>
                <x-field :label="__('complaints.admin.internal_notes')" name="internal_notes" :hint="__('complaints.admin.internal_notes_hint')">
                    <textarea id="f-internal_notes" name="internal_notes" class="input min-h-28" maxlength="5000">{{ old('internal_notes', $complaint->internal_notes) }}</textarea>
                </x-field>
                <x-button variant="accent">{{ __('complaints.admin.save') }}</x-button>
            </form>
        </x-card>
    </div>
</div>
@endsection
