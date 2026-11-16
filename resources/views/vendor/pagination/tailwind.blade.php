{{--
    SALE7LI-themed pagination. Overrides Laravel's default `pagination::tailwind`
    view. Uses the app design tokens (ink / paper / line / surface), the shared
    rounded scale and typography. RTL-safe: it is a plain flex row so the browser
    reverses order under dir="rtl", and the chevrons carry `rtl-flip`.
    Compact on mobile (prev · "n / total" · next); numbered window from `sm` up.
--}}
@php
    $linkBase = 'inline-flex min-h-[44px] items-center gap-1.5 rounded-xl border border-line bg-surface px-3 text-sm font-bold text-ink transition hover:border-line-strong hover:bg-surface-2';
    $disabledBase = 'inline-flex min-h-[44px] items-center gap-1.5 rounded-xl border border-line bg-surface-2 px-3 text-sm font-bold text-faint';
    $pageBase = 'grid min-h-[44px] min-w-[44px] place-items-center rounded-xl border border-line bg-surface px-2 text-sm font-bold text-ink transition hover:border-line-strong hover:bg-surface-2';
    $pageActive = 'grid min-h-[44px] min-w-[44px] place-items-center rounded-xl border border-ink bg-ink px-2 text-sm font-bold text-white';
@endphp

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('pagination.nav') }}"
         class="flex items-center justify-between gap-3">

        {{-- Previous --}}
        @if ($paginator->onFirstPage())
            <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}" class="{{ $disabledBase }}">
                <x-icon name="chevron-left" class="h-4 w-4" />
                <span class="hidden sm:inline">{{ __('pagination.previous') }}</span>
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ __('pagination.previous') }}" class="{{ $linkBase }}">
                <x-icon name="chevron-left" class="h-4 w-4" />
                <span class="hidden sm:inline">{{ __('pagination.previous') }}</span>
            </a>
        @endif

        {{-- Numbered window (sm and up) --}}
        <span class="hidden items-center gap-1.5 sm:flex">
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span aria-hidden="true" class="px-1 text-faint">{{ $element }}</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="{{ $pageActive }}">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" aria-label="{{ __('pagination.go_to', ['page' => $page]) }}" class="{{ $pageBase }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach
        </span>

        {{-- Compact position (below sm) --}}
        <span class="text-sm font-bold text-faint tabular-nums sm:hidden">
            {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
        </span>

        {{-- Next --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ __('pagination.next') }}" class="{{ $linkBase }}">
                <span class="hidden sm:inline">{{ __('pagination.next') }}</span>
                <x-icon name="chevron-right" class="h-4 w-4" />
            </a>
        @else
            <span aria-disabled="true" aria-label="{{ __('pagination.next') }}" class="{{ $disabledBase }}">
                <span class="hidden sm:inline">{{ __('pagination.next') }}</span>
                <x-icon name="chevron-right" class="h-4 w-4" />
            </span>
        @endif
    </nav>
@endif
