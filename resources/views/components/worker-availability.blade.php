@props([
    'availability' => null,   // iterable of WorkerAvailability rows
    'editable' => false,       // editable renders form inputs; parent supplies the <form>
])
@php
    // day_of_week: 0 = Monday … 6 = Sunday (matches the order shown on the
    // public profile). Names are localised through the app's Carbon locale.
    // Times are application local time — the `time` columns carry no timezone.
    $rows = collect($availability ?? []);
    $byDay = $rows->keyBy('day_of_week');
    $dayName = fn (int $i) => \Carbon\CarbonImmutable::now()->startOfWeek()->addDays($i)->translatedFormat('l');
    $hhmm = fn (?string $t) => $t ? \Illuminate\Support\Str::of($t)->substr(0, 5) : null;
    $published = $rows->where('is_available', true)->sortBy('day_of_week');
@endphp

@if ($editable)
    <div {{ $attributes->merge(['class' => 'space-y-2.5']) }}>
        @for ($i = 0; $i < 7; $i++)
            @php($slot = $byDay->get($i))
            @php($isOn = (bool) old("days.$i.is_available", $slot?->is_available))
            <div class="rounded-xl border border-line p-3 sm:flex sm:items-center sm:gap-4"
                 x-data="{ on: {{ $isOn ? 'true' : 'false' }} }">
                <label class="flex items-center gap-2.5 font-bold sm:w-40">
                    <input type="hidden" name="days[{{ $i }}][is_available]" value="0">
                    <input type="checkbox" name="days[{{ $i }}][is_available]" value="1" x-model="on"
                           @checked($isOn)
                           class="h-4 w-4 rounded border-line text-ink focus-visible:ring-2 focus-visible:ring-ink/25">
                    {{ $dayName($i) }}
                </label>
                <div class="mt-2 flex items-center gap-2 sm:mt-0" :class="on ? '' : 'opacity-40'">
                    <label for="av-{{ $i }}-start" class="sr-only">{{ __('workers.av_start') }} — {{ $dayName($i) }}</label>
                    <input id="av-{{ $i }}-start" type="time" name="days[{{ $i }}][start]"
                           value="{{ $hhmm(old("days.$i.start", $slot?->start_time)) }}"
                           class="input w-32" x-bind:disabled="!on">
                    <span class="t-muted" aria-hidden="true">–</span>
                    <label for="av-{{ $i }}-end" class="sr-only">{{ __('workers.av_end') }} — {{ $dayName($i) }}</label>
                    <input id="av-{{ $i }}-end" type="time" name="days[{{ $i }}][end]"
                           value="{{ $hhmm(old("days.$i.end", $slot?->end_time)) }}"
                           class="input w-32" x-bind:disabled="!on">
                </div>
            </div>
        @endfor
    </div>
@elseif ($published->isNotEmpty())
    <ul {{ $attributes->merge(['class' => 'space-y-1.5 t-muted']) }}>
        @foreach ($published as $slot)
            <li class="flex items-center justify-between gap-4">
                <span class="font-semibold text-ink">{{ $dayName((int) $slot->day_of_week) }}</span>
                <span>{{ $hhmm($slot->start_time) }}–{{ $hhmm($slot->end_time) }}</span>
            </li>
        @endforeach
    </ul>
@else
    <p {{ $attributes->merge(['class' => 't-small']) }}>{{ __('workers.availability_none') }}</p>
@endif
