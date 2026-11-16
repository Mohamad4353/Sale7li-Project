@extends('layouts.app')
@section('content')
@php($title = __('support.title'))

<div class="container-app max-w-3xl py-8 sm:py-10">
    <x-page-header :eyebrow="__('nav.support')" :title="__('support.title')" :subtitle="__('support.sub')" />

    <div class="mt-8 grid gap-6">
        <x-card>
            <h2 class="t-section">{{ __('support.contact_title') }}</h2>
            <p class="mt-2 t-muted">{{ __('support.contact_body') }}</p>
            <p class="mt-4 t-label">{{ __('support.email_label') }}</p>
            <a href="mailto:{{ $supportEmail }}" class="text-lg font-extrabold text-ink hover:underline">{{ $supportEmail }}</a>

            @if (filled(config('sale7li.support_whatsapp')))
                <div class="mt-5 border-t border-line pt-5">
                    <x-whatsapp-button
                        :phone="config('sale7li.support_whatsapp')"
                        :label="__('whatsapp.contact_support')" />
                </div>
            @endif
        </x-card>

        <x-card>
            <h2 class="t-section">{{ __('support.report_title') }}</h2>
            <p class="mt-2 t-muted">{{ __('support.report_body') }}</p>
            @if (auth()->user()->role === 'customer')
                <x-button :href="route('customer.requests.index')" variant="secondary" class="mt-4">
                    {{ __('support.go_to_requests') }}
                </x-button>
            @endif
        </x-card>

        <x-card padding="p-0" class="overflow-hidden">
            <h2 class="t-section px-6 pt-6">{{ __('complaints.filed_title') }}</h2>
            <div class="mt-3 divide-y divide-line">
                @forelse ($complaints as $complaint)
                    <div class="flex items-center justify-between gap-3 px-6 py-4">
                        <div class="min-w-0">
                            <div class="truncate font-bold">{{ __('complaints.categories.'.$complaint->category) }}</div>
                            <div class="t-small">
                                {{ $complaint->request?->reference ?? __('complaints.admin.no_request') }} ·
                                <x-date :value="$complaint->created_at" />
                            </div>
                        </div>
                        <x-status type="complaint" :value="$complaint->status" />
                    </div>
                @empty
                    <x-empty icon="chat" :title="__('complaints.none_filed')" />
                @endforelse
            </div>
        </x-card>
    </div>
</div>
@endsection
