{{--
    Shared filter fields for the services / professionals discovery pages.
    Rendered inside a <form method="GET"> — once in the desktop sidebar and once
    in the mobile drawer.

    Expects:
      $categories   Collection of active ServiceCategory (id, name, name_ar, slug, icon)
      $filters      App\Marketplace\DiscoveryFilters
      $showPrice    bool   — show the price range inputs
      $showRating   bool   — show the minimum-rating radios
      $priceBounds  array  — [min, max] for placeholders (when $showPrice)
--}}
@php($showPrice = $showPrice ?? false)
@php($showRating = $showRating ?? false)

<div class="space-y-6">
    <div>
        <p class="field-label">{{ __('services.category') }}</p>
        <div class="mt-2 space-y-1.5">
            <label class="flex cursor-pointer items-center gap-2 text-sm">
                <input type="radio" name="category" value="" @checked(! $filters->category) class="border-line">
                {{ __('services.all_categories') }}
            </label>
            @foreach ($categories as $c)
                <label class="flex cursor-pointer items-center gap-2 text-sm">
                    <input type="radio" name="category" value="{{ $c->slug }}" @checked($filters->category === $c->slug) class="border-line">
                    <span>{{ $c->icon }} {{ $c->localized_name }}</span>
                </label>
            @endforeach
        </div>
    </div>

    @if ($showPrice)
        <div>
            <p class="field-label">{{ __('services.price') }} ($)</p>
            <div class="mt-2 flex items-center gap-2">
                <label class="sr-only" for="mf-min">{{ __('services.min_price') }}</label>
                <input id="mf-min" type="number" inputmode="numeric" min="0" name="min_price" value="{{ $filters->minPrice }}"
                       placeholder="{{ $priceBounds[0] ?? __('services.min_price') }}" class="input">
                <span class="text-faint">–</span>
                <label class="sr-only" for="mf-max">{{ __('services.max_price') }}</label>
                <input id="mf-max" type="number" inputmode="numeric" min="0" name="max_price" value="{{ $filters->maxPrice }}"
                       placeholder="{{ $priceBounds[1] ?? __('services.max_price') }}" class="input">
            </div>
        </div>
    @endif

    @if ($showRating)
        <div>
            <p class="field-label">{{ __('workers.min_rating') }}</p>
            <div class="mt-2 space-y-1.5">
                <label class="flex cursor-pointer items-center gap-2 text-sm">
                    <input type="radio" name="min_rating" value="" @checked(! $filters->minRating) class="border-line">
                    {{ __('workers.rating_any') }}
                </label>
                @foreach ([5, 4, 3] as $r)
                    <label class="flex cursor-pointer items-center gap-2 text-sm">
                        <input type="radio" name="min_rating" value="{{ $r }}" @checked($filters->minRating === $r) class="border-line">
                        <x-rating :value="$r" :showValue="false" />
                        <span>{{ __('workers.rating_stars', ['n' => $r]) }}</span>
                    </label>
                @endforeach
            </div>
        </div>
    @endif

    <div class="flex flex-col gap-2 border-t border-line pt-4">
        <x-button type="submit" size="sm">{{ __('services.apply') }}</x-button>
        @if ($filters->isActive())
            <a href="{{ url()->current() }}" class="text-center text-[13px] font-bold text-muted transition hover:text-ink">
                {{ __('services.clear_filters') }}
            </a>
        @endif
    </div>
</div>
