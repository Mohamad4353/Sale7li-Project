@props([
    'series' => [],        // list<array{label:string, value:int|float}>
    'title' => null,
    'formatValue' => null, // ?callable(int|float): string
    'dataChart' => null,   // stable test/automation selector
])
@php
    $points = collect($series)->values();
    // Time flows in the reading direction: oldest -> newest, left -> right in
    // LTR and right -> left in RTL (so the SVG's index 0 sits at the leading edge).
    if (app()->getLocale() === 'ar') {
        $points = $points->reverse()->values();
    }

    $count = $points->count();
    $values = $points->pluck('value')->map(fn ($v) => (float) $v);
    $peak = (float) ($values->max() ?? 0);
    $hasData = $count > 0 && $peak > 0;
    $fmt = $formatValue ?? fn ($v) => number_format((float) $v);

    $w = 640; $h = 180;
    $padX = 8; $padTop = 12; $padBottom = 24;
    $plotW = $w - ($padX * 2);
    $plotH = $h - $padTop - $padBottom;
    $max = max($peak, 1.0);
    $px = fn ($i) => $padX + ($count <= 1 ? $plotW / 2 : ($i / ($count - 1)) * $plotW);
    $py = fn ($v) => $padTop + $plotH - (((float) $v / $max) * $plotH);

    $poly = $points
        ->map(fn ($p, $i) => round($px($i), 1).','.round($py($p['value']), 1))
        ->implode(' ');
    $area = $hasData
        ? round($px(0), 1).','.($padTop + $plotH).' '.$poly.' '.round($px($count - 1), 1).','.($padTop + $plotH)
        : '';
    $labelStep = max(1, (int) ceil($count / 6));
@endphp
<x-card :data-chart="$dataChart">
    @if ($title)<h3 class="t-section">{{ $title }}</h3>@endif

    @unless ($hasData)
        <p class="mt-3 t-muted">{{ __('dashboard.chart_no_data') }}</p>
    @else
        <div class="mt-4 overflow-x-auto">
            <svg viewBox="0 0 {{ $w }} {{ $h }}" role="img"
                 aria-label="{{ $title ?? __('dashboard.chart_line_label') }}"
                 class="block w-full min-w-[380px]" style="aspect-ratio: {{ $w }} / {{ $h }}">
                <polygon points="{{ $area }}" class="fill-ink/10" />
                <polyline points="{{ $poly }}" fill="none" class="stroke-ink" stroke-width="2"
                          stroke-linejoin="round" stroke-linecap="round" />
                @foreach ($points as $i => $p)
                    @if ($i % $labelStep === 0 || $i === $count - 1)
                        <text x="{{ round($px($i), 1) }}" y="{{ $h - 8 }}" text-anchor="middle"
                              class="fill-muted" style="font-size:10px">{{ $p['label'] }}</text>
                    @endif
                @endforeach
            </svg>
        </div>
        {{-- Screen-reader-only representation of the plotted points. The SVG
             stays role="img" for its visual identity; this list carries the
             actual data without adding anything on screen. --}}
        <ul class="sr-only">
            @foreach ($points as $p)
                <li>{{ $p['label'] }}: {{ $fmt($p['value']) }}</li>
            @endforeach
        </ul>
        <p class="mt-2 t-small text-muted">{{ __('dashboard.chart_peak', ['value' => $fmt($peak)]) }}</p>
    @endunless
</x-card>
