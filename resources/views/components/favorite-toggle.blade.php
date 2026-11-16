@props([
    'worker',
    'favorited' => null,   // pass a bool to avoid a per-render lookup
])
@php
    $isCustomer = auth()->check() && auth()->user()->role === 'customer';
    if ($isCustomer && $favorited === null) {
        $favorited = auth()->user()->favoriteWorkers()->whereKey($worker->id)->exists();
    }
@endphp
@if ($isCustomer)
    <form method="POST" action="{{ route('customer.favorites.toggle', $worker) }}" data-no-busy
          x-data="favoriteToggle({{ $favorited ? 'true' : 'false' }})"
          @submit.prevent="toggle($event.target)"
          {{ $attributes }}>
        @csrf
        <button type="submit"
                class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-line bg-surface px-3 py-2 text-[13px] font-bold transition hover:bg-paper"
                :class="on ? 'text-rose-600' : 'text-muted'"
                :aria-pressed="on.toString()"
                :aria-label="on ? @js(__('favorites.saved')) : @js(__('favorites.save'))">
            <x-icon name="star" class="h-4 w-4" ::class="on ? 'fill-current' : ''" />
            <span x-show="!on">{{ __('favorites.save') }}</span>
            <span x-show="on" x-cloak>{{ __('favorites.saved') }}</span>
        </button>
    </form>
@endif
