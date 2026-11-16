@props(['worker'])
@php($p = $worker->workerProfile)
@php($services = $worker->workerServices ?? collect())
<a href="{{ route('workers.show', $worker) }}"
   {{ $attributes->merge(['class' => 'group flex flex-col rounded-2xl border border-line bg-surface p-5 transition hover:border-line-strong hover:shadow-raised']) }}>
    <div class="flex items-center gap-3">
        <x-avatar :name="$worker->name" :src="$p && $p->photo ? route('workers.photo', $worker) : null" />
        <div class="min-w-0">
            <div class="flex items-center gap-1.5">
                <span class="truncate font-extrabold">{{ $worker->name }}</span>
                @if ($p && $p->verification_status === 'verified')
                    <x-icon name="shield" class="h-3.5 w-3.5 shrink-0 text-success" aria-label="{{ __('status.verification.verified') }}" />
                @endif
            </div>
            <div class="mt-0.5">
                <x-rating :average="$p?->rating_average ?? 0" :count="$p?->rating_count ?? 0" />
            </div>
            <x-availability-badge :profile="$p" class="mt-1" />
        </div>
    </div>

    @if ($services->isNotEmpty())
        <div class="mt-4 flex flex-wrap gap-1.5">
            @foreach ($services->take(3) as $ws)
                <x-badge tone="neutral">{{ $ws->category?->localized_name }}</x-badge>
            @endforeach
            @if ($services->count() > 3)
                <x-badge tone="neutral">+{{ $services->count() - 3 }}</x-badge>
            @endif
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between border-t border-line pt-3 t-small">
        <span>{{ trans_choice('workers.experience_count', (int) ($p?->experience_years ?? 0), ['count' => (int) ($p?->experience_years ?? 0)]) }}</span>
        @if ($p && $p->service_radius_km)
            <span>{{ __('workers.service_area_km', ['km' => (int) $p->service_radius_km]) }}</span>
        @endif
    </div>
</a>
