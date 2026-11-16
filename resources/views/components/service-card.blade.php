@props(['service'])
@php($pros = $service->professionals_count ?? null)
<a href="{{ route('services.show', $service) }}"
   {{ $attributes->merge(['class' => 'group flex flex-col rounded-2xl border border-line bg-surface p-6 transition hover:border-line-strong hover:shadow-raised']) }}>
    <span class="grid h-14 w-14 place-items-center rounded-2xl bg-paper text-2xl transition group-hover:bg-brand">{{ $service->icon ?: '🛠️' }}</span>
    <h3 class="mt-5 t-card">{{ $service->localized_name }}</h3>
    <p class="mt-2 line-clamp-2 flex-1 t-muted">{{ $service->localized_description }}</p>
    <div class="mt-5 flex items-center justify-between">
        <span class="text-sm font-bold">{{ __('services.from') }} <x-money :amount="$service->base_price" /></span>
        @if (! is_null($pros))
            <span class="t-small">{{ trans_choice('services.pro_count', $pros, ['count' => $pros]) }}</span>
        @endif
    </div>
    <span class="mt-4 inline-flex items-center gap-1 text-[13px] font-bold text-ink">
        {{ __('services.view_service') }}<x-icon name="arrow-right" class="h-3.5 w-3.5" />
    </span>
</a>
