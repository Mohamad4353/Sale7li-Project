@props([
    'title',
    'eyebrow' => null,
    'subtitle' => null,
    'back' => null,        // url for a "back" link
    'backLabel' => null,
])
<div {{ $attributes->merge(['class' => 'flex flex-col gap-4 md:flex-row md:items-end md:justify-between md:gap-6']) }}>
    <div class="min-w-0">
        @if ($back)
            <a href="{{ $back }}" class="mb-2 -ms-1 inline-flex items-center gap-1.5 px-1 py-1 text-[13px] font-bold text-muted transition hover:text-ink">
                <x-icon name="arrow-left" class="h-4 w-4" />{{ $backLabel ?? __('messages.back') }}
            </a>
        @endif
        @if ($eyebrow)<p class="t-eyebrow">{{ $eyebrow }}</p>@endif
        <h1 class="t-page {{ $eyebrow ? 'mt-1' : '' }}">{{ $title }}</h1>
        @if ($subtitle)<p class="mt-2 t-muted max-w-2xl">{{ $subtitle }}</p>@endif
    </div>
    @if (isset($actions))
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endif
</div>
