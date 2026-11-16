@extends('layouts.app')
@section('content')
@php($title = __('requests.my_requests'))

<div class="container-app py-8 sm:py-10">
    <x-page-header :eyebrow="__('requests.eyebrow')" :title="__('requests.my_requests')">
        <x-slot:actions>
            <x-button :href="route('customer.booking.create')" variant="accent">
                <x-icon name="plus" class="h-4 w-4" />{{ __('requests.new_request') }}
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <x-request-status-filter route="customer.requests.index" :current="$status" class="mt-7" />

    <x-card padding="p-0" class="mt-4 overflow-hidden">
        <div class="divide-y divide-line">
            @forelse ($requests as $r)
                <a href="{{ route('customer.requests.show', $r) }}" class="flex flex-col gap-3 p-5 transition hover:bg-surface-2 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <div class="flex items-center gap-3">
                            <span class="text-xl">{{ $r->category->icon }}</span>
                            <div class="min-w-0">
                                <div class="truncate font-bold">{{ $r->category->localized_name }}</div>
                                <div class="t-small">{{ $r->reference }} · <x-date :value="$r->created_at" /></div>
                            </div>
                        </div>
                        <p class="mt-2 line-clamp-2 max-w-xl t-muted">{{ $r->problem_description }}</p>
                    </div>
                    <x-status type="request" :value="$r->worker_id && $r->status === 'pending' ? 'awaiting_acceptance' : $r->status" class="shrink-0" />
                </a>
            @empty
                <x-empty icon="clipboard" :title="__('requests.none_title')" :message="__('requests.none_hint')">
                    <x-slot:action>
                        <x-button :href="route('customer.booking.create')" variant="accent" size="sm">{{ __('requests.new_request') }}</x-button>
                    </x-slot:action>
                </x-empty>
            @endforelse
        </div>
    </x-card>

    <div class="mt-5">{{ $requests->links() }}</div>
</div>
@endsection
