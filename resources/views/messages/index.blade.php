@extends('layouts.app')
@section('content')
@php($title = __('messages.inbox.title'))
@php($me = auth()->id())

<div class="container-app py-8 sm:py-10">
    <x-page-header :title="__('messages.inbox.title')" :subtitle="__('messages.inbox.sub')" />

    <x-card padding="p-0" class="mt-7 overflow-hidden">
        <div class="divide-y divide-line">
            @forelse ($conversations as $conversation)
                @php($other = $conversation->counterpartFor($me))
                @php($last = $conversation->latestMessage)
                <a href="{{ route('messages.show', $conversation->request) }}"
                   class="flex items-start justify-between gap-4 p-5 transition hover:bg-surface-2">
                    <div class="flex min-w-0 items-start gap-3">
                        <x-avatar :name="$other?->name ?? ''" size="sm" class="mt-0.5" />
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="font-bold">{{ $other?->name ?? __('messages.none') }}</span>
                                @if ($conversation->request)
                                    <span class="t-small">· {{ $conversation->request->reference }}</span>
                                @endif
                            </div>
                            <p class="mt-1 line-clamp-1 t-muted">
                                {{ $last?->body ?? __('messages.inbox.no_messages_yet') }}
                            </p>
                        </div>
                    </div>
                    <div class="flex shrink-0 flex-col items-end gap-1.5">
                        @if ($last)
                            <span class="t-small">{{ $last->created_at->diffForHumans() }}</span>
                        @endif
                        @if ($conversation->unread_count > 0)
                            <span class="rounded-full bg-brand px-2 py-0.5 text-[10px] font-extrabold text-ink">
                                {{ __('messages.inbox.unread_count', ['count' => $conversation->unread_count]) }}
                            </span>
                        @endif
                    </div>
                </a>
            @empty
                <x-empty icon="chat" :title="__('messages.inbox.empty_title')" :message="__('messages.inbox.empty_hint')" />
            @endforelse
        </div>
    </x-card>

    <div class="mt-5">{{ $conversations->links() }}</div>
</div>
@endsection
