@props([
    'status',
    'history' => [],   // ['submitted' => Carbon, 'accepted' => Carbon, ...] from App\Support\RequestTimeline
    'worker' => null,   // the assigned professional, when one is attached
])
@php
    // Conceptual lifecycle. 'submitted' and 'assigned' are not statuses — they
    // map to request creation and worker_id being set while still 'pending'.
    $flow = ['submitted', 'assigned', 'accepted', 'scheduled', 'on_the_way', 'in_progress', 'completed'];
    $terminal = in_array($status, ['cancelled', 'rejected'], true);
    $assigned = $worker !== null || isset($history['assigned']);

    $indexOf = [
        'pending' => $assigned ? 1 : 0,
        'accepted' => 2, 'scheduled' => 3, 'on_the_way' => 4, 'in_progress' => 5, 'completed' => 6,
    ];
    $current = $terminal ? null : ($indexOf[$status] ?? 0);

    $fmt = fn ($ts) => $ts?->translatedFormat('M j, Y · H:i');
@endphp
<ol {{ $attributes->merge(['class' => 'relative space-y-6']) }}>
    @foreach ($flow as $i => $key)
        @php
            $ts = $history[$key] ?? null;
            $done = ! $terminal && $current !== null && ($i < $current || ($i === $current && $key === 'completed'));
            $isCurrent = ! $terminal && $i === $current && $key !== 'completed';
            $reached = $done || $isCurrent;
        @endphp
        <li class="relative flex gap-4">
            @unless ($loop->last && ! $terminal)
                <span aria-hidden="true"
                      class="absolute start-4 top-8 -bottom-6 w-px {{ $done ? 'bg-ink' : 'bg-line' }}"></span>
            @endunless

            <span @class([
                'relative z-10 grid h-8 w-8 shrink-0 place-items-center rounded-full border-2 text-xs font-extrabold',
                'border-ink bg-ink text-white' => $done,
                'border-ink bg-surface text-ink' => $isCurrent,
                'border-line bg-surface text-faint' => ! $reached,
            ])>
                @if ($done)
                    <x-icon name="check" class="h-4 w-4" />
                @elseif ($isCurrent)
                    <span class="h-2.5 w-2.5 rounded-full bg-ink" aria-hidden="true"></span>
                @else
                    {{ $i + 1 }}
                @endif
            </span>

            <span class="min-w-0 pt-1">
                <span class="block font-extrabold {{ $reached ? 'text-ink' : 'text-faint' }}">
                    {{ __('requests.steps.'.$key) }}
                    <span class="sr-only">— {{ $done
                        ? __('requests.timeline_a11y.done')
                        : ($isCurrent ? __('requests.timeline_a11y.current') : __('requests.timeline_a11y.upcoming')) }}</span>
                </span>

                @if ($key === 'assigned' && $worker && $status === 'pending')
                    <span class="mt-0.5 block t-small">{{ __('requests.assigned_to', ['name' => $worker->name]) }}</span>
                @elseif ($isCurrent)
                    <span class="mt-0.5 block t-small">{{ __('status.request.'.$status) }}</span>
                @endif

                @if ($ts)
                    <time datetime="{{ $ts->toIso8601String() }}" class="mt-0.5 block t-small tabular-nums">{{ $fmt($ts) }}</time>
                @endif
            </span>
        </li>
    @endforeach

    @if ($terminal)
        <li class="relative flex gap-4">
            <span class="relative z-10 grid h-8 w-8 shrink-0 place-items-center rounded-full border-2 border-rose-300 bg-rose-50 text-rose-600">
                <x-icon name="close" class="h-4 w-4" />
            </span>
            <span class="pt-1">
                <span class="block font-extrabold text-rose-600">
                    {{ __('status.request.'.$status) }}
                    <span class="sr-only">— {{ __('requests.timeline_a11y.current') }}</span>
                </span>
                @if (($history[$status] ?? null))
                    <time datetime="{{ $history[$status]->toIso8601String() }}" class="mt-0.5 block t-small tabular-nums">{{ $fmt($history[$status]) }}</time>
                @endif
            </span>
        </li>
    @endif
</ol>
