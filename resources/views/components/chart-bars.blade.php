@props([
    'series' => [],        // list<array{label:string, value:int|float}>
    'title' => null,
    'formatValue' => null, // ?callable(int|float): string
    'dataChart' => null,   // stable test/automation selector
])
@php
    $rows = collect($series)->values();
    $count = $rows->count();
    $max = max(1.0, (float) $rows->pluck('value')->map(fn ($v) => (float) $v)->max());
    $hasData = $count > 0;
    $fmt = $formatValue ?? fn ($v) => number_format((float) $v);
    $rtl = app()->getLocale() === 'ar';
@endphp
{{--
    The category label is rendered as ordinary HTML text — SVG <text> mangles
    Arabic (no reliable contextual shaping / bidi in the SVG text engine, which
    produced clipped single-glyph fragments). HTML handles shaping, direction
    and wrapping natively from the page's `dir`. Only the bar stays SVG: two
    <rect>s with no text, so there is nothing left for the SVG engine to break.
    Still server-rendered, no JS, no dependency.
--}}
<x-card :data-chart="$dataChart">
    @if ($title)<h3 class="t-section">{{ $title }}</h3>@endif

    @unless ($hasData)
        <p class="mt-3 t-muted">{{ __('dashboard.chart_no_data') }}</p>
    @else
        {{-- The category label + value in each row are real text; leaving the
             list as a plain list (not role="img") lets assistive tech read
             every data point instead of collapsing it to the label alone.
             The bar itself stays aria-hidden. --}}
        <ul class="mt-4 space-y-2.5" aria-label="{{ $title ?? __('dashboard.chart_bars_label') }}">
            @foreach ($rows as $row)
                @php
                    $value = (float) $row['value'];
                    $pct = round(($value / $max) * 100, 2);
                    $drawPct = $value > 0 ? max($pct, 1.5) : 0;
                @endphp
                <li class="flex items-center gap-3 text-sm">
                    <span class="w-36 shrink-0 text-end leading-snug break-words text-muted">{{ $row['label'] }}</span>
                    <span class="h-6 flex-1 overflow-hidden rounded-md">
                        <svg viewBox="0 0 100 24" preserveAspectRatio="none" class="block h-full w-full" aria-hidden="true">
                            <rect x="0" y="0" width="100" height="24" class="fill-line" />
                            @if ($drawPct > 0)
                                <rect x="{{ $rtl ? round(100 - $drawPct, 2) : 0 }}" y="0" width="{{ $drawPct }}" height="24" class="fill-ink" />
                            @endif
                        </svg>
                    </span>
                    <span class="w-12 shrink-0 text-end font-bold tabular-nums" dir="ltr">{{ $fmt($value) }}</span>
                </li>
            @endforeach
        </ul>
    @endunless
</x-card>
