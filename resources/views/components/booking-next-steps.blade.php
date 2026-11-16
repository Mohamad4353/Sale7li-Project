{{-- The real SALE7LI request lifecycle, shown to the customer before they book.
     Nothing here implies a price or a confirmed booking before quotation/payment. --}}
<x-card {{ $attributes->merge(['class' => 'bg-paper']) }}>
    <h2 class="t-card">{{ __('bookings.next_title') }}</h2>
    <ol class="mt-4 space-y-3">
        @foreach (['next_1', 'next_2', 'next_3', 'next_4', 'next_5', 'next_6'] as $i => $key)
            <li class="flex items-start gap-3 text-sm">
                <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-ink text-xs font-bold text-white">{{ $i + 1 }}</span>
                <span class="pt-0.5">{{ __('bookings.'.$key) }}</span>
            </li>
        @endforeach
    </ol>
    <p class="mt-4 rounded-xl bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-900">
        {{ __('bookings.next_note') }}
    </p>
</x-card>
