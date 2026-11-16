@props(['active' => 0])
{{--
    One filter panel, two presentations:
      - lg and up: a static sticky sidebar (the grid's first column).
      - below lg: a "Filters" button that opens a focus-trapped slide-over
        holding the SAME markup, so the form fields exist exactly once.
--}}
<div x-data="{ open: false, get isMobile() { return window.matchMedia('(max-width: 1023px)').matches } }"
     @keydown.escape.window="open = false"
     class="lg:sticky lg:top-24">

    {{-- Mobile trigger (Alpine-only; hidden by CSS when JS has not run) --}}
    <button type="button" @click="open = true" data-filter-trigger
            class="inline-flex items-center gap-2 rounded-xl border border-line bg-surface px-4 py-2.5 text-sm font-bold lg:hidden">
        <x-icon name="sliders" class="h-4 w-4" />
        {{ __('services.filters') }}
        @if ($active)<span class="grid h-5 min-w-5 place-items-center rounded-full bg-ink px-1 text-[11px] font-extrabold text-white">{{ $active }}</span>@endif
    </button>

    {{-- Backdrop (mobile only) --}}
    <div x-show="open" x-cloak x-transition.opacity @click="open = false" class="fixed inset-0 z-[60] bg-ink/40 lg:hidden"></div>

    {{-- Panel: slide-over on mobile, plain sidebar card on lg+. Without JS,
         `html:not(.js) [data-filter-panel]` drops it into normal flow so the
         filters stay reachable on small screens. --}}
    <div x-bind:class="open ? 'translate-x-0' : 'translate-x-full rtl:-translate-x-full lg:translate-x-0'"
         x-trap="open && isMobile"
         data-filter-panel
         class="fixed inset-y-0 end-0 z-[60] flex w-[88%] max-w-sm translate-x-full flex-col overflow-y-auto bg-surface shadow-modal transition-transform duration-200 rtl:-translate-x-full
                lg:static lg:z-auto lg:w-auto lg:max-w-none lg:translate-x-0 lg:rounded-2xl lg:border lg:border-line lg:p-5 lg:shadow-none lg:rtl:translate-x-0"
         role="dialog" aria-modal="true" aria-label="{{ __('services.filters') }}">
        <div class="flex items-center justify-between border-b border-line p-4 lg:hidden">
            <span class="font-extrabold">{{ __('services.filters') }}</span>
            <button type="button" @click="open = false" data-filter-dismiss class="rounded-lg p-1 text-muted" aria-label="{{ __('messages.close') }}">
                <x-icon name="close" class="h-5 w-5" />
            </button>
        </div>
        <div class="flex-1 p-4 lg:p-0">{{ $slot }}</div>
    </div>
</div>
