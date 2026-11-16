@php($mine = $message->sender_id === auth()->id())
<div class="flex {{ $mine ? 'justify-end' : 'justify-start' }}" data-message-id="{{ $message->id }}">
    <div class="max-w-[80%] rounded-2xl px-4 py-3 {{ $mine ? 'bg-ink text-white' : 'border border-line bg-surface text-ink' }}">
        <div class="whitespace-pre-wrap text-sm">{{ $message->body }}</div>
        @if (!empty($message->attachments))
            <div class="mt-2 flex flex-wrap gap-2">
                @foreach ($message->attachments as $i => $path)
                    <a href="{{ route('attachments.message', [$message, $i]) }}" target="_blank" rel="noopener"
                       class="inline-flex items-center gap-1 rounded-lg bg-black/10 px-2 py-1 text-xs font-semibold">
                        <x-icon name="clipboard" class="h-3 w-3" />{{ __('messages.chat.view') }}
                    </a>
                @endforeach
            </div>
        @endif
        <div class="mt-1 text-[10px] opacity-60">{{ $message->created_at->translatedFormat('M d · H:i') }}</div>
    </div>
</div>
