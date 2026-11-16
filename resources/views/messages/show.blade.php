@extends('layouts.app')
@section('content')
@php($title = $conversation->request->category->localized_name)
@php($storeRoute = auth()->user()->role === 'customer'
    ? route('customer.messages.store', $conversation->request)
    : route('worker.messages.store', $conversation->request))
@php($backRoute = auth()->user()->role === 'admin' && $conversation->request
    ? route('admin.requests.show', $conversation->request)
    : route('messages.index'))
@php($ordered = $messages->getCollection()->sortBy('id')->values())

<div class="container-app max-w-3xl py-8 sm:py-10">
    <x-page-header
        :back="$backRoute"
        :backLabel="__('messages.chat.back')"
        :eyebrow="__('messages.chat.request', ['reference' => $conversation->request->reference])"
        :title="$conversation->request->category->localized_name" />

    <x-card padding="p-0" class="mt-6 overflow-hidden"
            x-data="chatPoll('{{ route('messages.poll', $conversation->request) }}', {{ $lastId }})">
        <div x-ref="thread"
             role="log" aria-live="polite" aria-relevant="additions text"
             aria-label="{{ $conversation->request->category->localized_name }}"
             class="max-h-[65vh] space-y-4 overflow-y-auto bg-paper p-5 sm:max-h-[55vh] sm:p-6">
            @if ($messages->hasMorePages())
                <div class="text-center">
                    <a href="{{ $messages->nextPageUrl() }}" class="inline-block rounded-xl border border-line bg-surface px-3 py-1.5 text-xs font-bold text-muted transition hover:text-ink">
                        {{ __('messages.chat.load_older') }}
                    </a>
                </div>
            @endif

            @forelse ($ordered as $m)
                @include('messages._bubble', ['message' => $m])
            @empty
                <x-empty icon="chat" :title="__('messages.chat.none_title')" :message="__('messages.chat.none_hint')" />
            @endforelse
        </div>

        <form method="POST" enctype="multipart/form-data" action="{{ $storeRoute }}" class="border-t border-line bg-surface p-4 sm:p-5">
            @csrf
            <x-field name="body">
                <div class="flex gap-3">
                    <textarea name="body" class="input min-h-14 flex-1" maxlength="3000" placeholder="{{ __('messages.chat.placeholder') }}" required>{{ old('body') }}</textarea>
                    <x-button class="self-end">{{ __('messages.chat.send') }}</x-button>
                </div>
            </x-field>
            {{-- React attachment field. The <x-react> slot is a real file input
                 so multipart submission still works before React mounts / with
                 JS off. Server validation (StoreMessageRequest) is unchanged. --}}
            <x-react root="AttachmentInput" class="mt-3 block"
                     :props="['strings' => ['attachments' => __('messages.chat.attachments')]]">
                <label class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-line bg-surface px-3 py-2 text-sm font-bold text-ink transition hover:border-ink">
                    <x-icon name="clipboard" class="h-4 w-4" />
                    <span>{{ __('messages.chat.attachments') }}</span>
                    <input type="file" name="attachments[]" accept="image/jpeg,image/png,image/webp" multiple class="sr-only">
                </label>
            </x-react>
        </form>
    </x-card>

    {{-- Phase 25: read-only recap of every photo exchanged in this thread. Each
         tile links through the same authorized attachments.message route the
         chat bubbles use; the poller does not refresh this list, so a photo
         sent this session appears here only after the page reloads. --}}
    @if ($attachments->isNotEmpty())
        <x-card class="mt-6">
            <h2 class="t-section">
                {{ __('messages.gallery.title') }}
                <span class="text-muted">({{ $attachments->count() }})</span>
            </h2>
            <ul class="mt-4 grid grid-cols-3 gap-2 sm:grid-cols-4">
                @foreach ($attachments as $item)
                    <li>
                        <a href="{{ $item['url'] }}" target="_blank" rel="noopener"
                           class="group block overflow-hidden rounded-xl border border-line">
                            <img src="{{ $item['url'] }}" alt="{{ __('messages.gallery.alt') }}"
                                 loading="lazy" decoding="async"
                                 class="aspect-square w-full bg-paper object-cover transition group-hover:opacity-90">
                        </a>
                        <p class="mt-1 text-[10px] text-muted">{{ $item['date']->translatedFormat('M d · H:i') }}</p>
                    </li>
                @endforeach
            </ul>
        </x-card>
    @endif
</div>
@endsection
