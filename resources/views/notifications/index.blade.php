@extends('layouts.app')
@section('content')
@php($title = __('notifications.title'))
@php($hasUnread = $notifications->getCollection()->contains(fn ($n) => is_null($n->read_at)))

<div class="container-app py-8 sm:py-10">
    <x-page-header :title="__('notifications.title')" :subtitle="__('notifications.sub')">
        @if ($hasUnread)
            <x-slot:actions>
                <form method="POST" action="{{ route('notifications.readAll') }}">
                    @csrf
                    <x-button variant="secondary" size="sm">
                        <x-icon name="check" class="h-4 w-4" />{{ __('notifications.mark_all_read') }}
                    </x-button>
                </form>
            </x-slot:actions>
        @endif
    </x-page-header>

    <x-card padding="p-0" class="mt-7 overflow-hidden">
        <div class="divide-y divide-line">
            @forelse ($notifications as $n)
                <a href="{{ route('notifications.open', $n) }}"
                   class="flex w-full items-start gap-3 p-5 text-start transition hover:bg-surface-2 {{ $n->read_at ? 'opacity-60' : '' }}">
                    <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $n->read_at ? 'bg-line' : 'bg-brand' }}" aria-hidden="true"></span>
                    <span class="min-w-0 flex-1">
                        <span class="block font-bold">{{ $n->renderedTitle() }}</span>
                        <span class="mt-0.5 block t-muted">{{ $n->renderedBody() }}</span>
                        <span class="mt-1.5 block t-small">{{ $n->created_at->diffForHumans() }}</span>
                    </span>
                    @unless ($n->read_at)
                        <span class="mt-1.5 shrink-0 rounded-full bg-brand/15 px-2 py-0.5 text-[10px] font-extrabold text-ink">{{ __('notifications.unread') }}</span>
                    @endunless
                </a>
            @empty
                <x-empty icon="bell" :title="__('notifications.none_title')" :message="__('notifications.none_hint')" />
            @endforelse
        </div>
    </x-card>

    <div class="mt-5">{{ $notifications->links() }}</div>
</div>
@endsection
