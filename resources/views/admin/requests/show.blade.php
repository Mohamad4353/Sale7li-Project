@extends('layouts.app')
@section('content')
@php($title = $serviceRequest->reference)
@php($r = $serviceRequest)
@php($profile = $r->worker?->workerProfile)

<div class="container-app py-8 sm:py-10">
    <x-page-header
        :back="route('admin.requests.index')"
        :backLabel="__('admin.requests_title')"
        :eyebrow="$r->reference"
        :title="$r->category->localized_name">
        <x-slot:actions>
            @if ($r->is_emergency)
                <x-badge tone="danger" dot>{{ __('admin.emergency') }}</x-badge>
            @endif
            <x-status type="request" :value="$r->worker_id && $r->status === 'pending' ? 'awaiting_acceptance' : $r->status" />
        </x-slot:actions>
    </x-page-header>

    <div class="mt-8 grid gap-6 lg:grid-cols-[1.6fr_1fr]">
        <div class="space-y-6">
            <x-card>
                <h2 class="t-section">{{ __('requests.problem') }}</h2>
                <p class="mt-2 t-body whitespace-pre-line">{{ $r->problem_description }}</p>

                <h3 class="mt-6 t-label">{{ __('requests.attachments') }}</h3>
                @if (!empty($r->problem_images))
                    <div class="mt-2 flex flex-wrap gap-3">
                        @foreach ($r->problem_images as $i => $path)
                            <a href="{{ route('attachments.request', [$r, 'problem', $i]) }}" target="_blank" rel="noopener"
                               class="grid h-20 w-20 place-items-center overflow-hidden rounded-xl border border-line bg-paper">
                                <img src="{{ route('attachments.request', [$r, 'problem', $i]) }}"
                                 alt="{{ __('requests.attachment_number', ['number' => $i + 1]) }}"
                                 width="80" height="80" loading="lazy" decoding="async" class="h-full w-full object-cover">
                            </a>
                        @endforeach
                    </div>
                @else
                    <p class="mt-1 t-small">{{ __('requests.no_attachments') }}</p>
                @endif

                @if (!empty($r->completion_images))
                    <h3 class="mt-6 t-label">{{ __('workers.completion_images') }}</h3>
                    <div class="mt-2 flex flex-wrap gap-3">
                        @foreach ($r->completion_images as $i => $path)
                            <a href="{{ route('attachments.request', [$r, 'completion', $i]) }}" target="_blank" rel="noopener"
                               class="grid h-20 w-20 place-items-center overflow-hidden rounded-xl border border-line bg-paper">
                                <img src="{{ route('attachments.request', [$r, 'completion', $i]) }}"
                                 alt="{{ __('requests.completion_image_number', ['number' => $i + 1]) }}"
                                 width="80" height="80" loading="lazy" decoding="async" class="h-full w-full object-cover">
                            </a>
                        @endforeach
                    </div>
                @endif
            </x-card>

            <x-card>
                <h2 class="t-section">{{ __('requests.timeline') }}</h2>
                <x-request-timeline :status="$r->status" class="mt-6" />
            </x-card>

            <x-card>
                <h2 class="t-section">{{ __('requests.quotation') }}</h2>
                @forelse ($r->quotations as $q)
                    <div class="mt-4 flex items-center justify-between border-t border-line pt-4 first:mt-3 first:border-0 first:pt-0">
                        <div>
                            <div class="font-bold">{{ $q->worker?->name }}</div>
                            <div class="mt-1"><x-status type="quote" :value="$q->status" /></div>
                        </div>
                        <b class="tabular-nums"><x-money :amount="$q->total" /></b>
                    </div>
                @empty
                    <p class="mt-3 t-muted">{{ __('requests.quotation_none') }}</p>
                @endforelse
            </x-card>

            <x-card>
                <h2 class="t-section">{{ __('admin.conversation_transcript') }}</h2>
                @forelse ($r->conversation?->messages ?? [] as $m)
                    <div class="mt-4 border-t border-line pt-4 first:mt-3 first:border-0 first:pt-0">
                        <div class="flex items-center justify-between t-small">
                            <span class="font-bold text-ink">{{ $m->sender?->name }}</span>
                            <span>{{ $m->created_at->translatedFormat('M d · H:i') }}</span>
                        </div>
                        <p class="mt-1 t-body whitespace-pre-line">{{ $m->body }}</p>
                        @if (!empty($m->attachments))
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach ($m->attachments as $i => $path)
                                    <a href="{{ route('attachments.message', [$m, $i]) }}" target="_blank" rel="noopener"
                                       class="inline-flex items-center gap-1 rounded-lg border border-line px-2 py-1 text-xs font-semibold">
                                        <x-icon name="clipboard" class="h-3 w-3" />{{ __('messages.chat.view') }}
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="mt-3 t-muted">{{ __('admin.no_conversation') }}</p>
                @endforelse
            </x-card>

            <x-card>
                <h2 class="t-section">{{ __('admin.request_complaints') }}</h2>
                @forelse ($r->complaints as $complaint)
                    <div class="mt-4 border-t border-line pt-4 first:mt-3 first:border-0 first:pt-0">
                        <div class="flex items-center justify-between">
                            <span class="font-bold">{{ __('complaints.categories.'.$complaint->category) }}</span>
                            <x-status type="complaint" :value="$complaint->status" />
                        </div>
                        <p class="mt-1 t-muted whitespace-pre-line">{{ $complaint->description }}</p>
                        <p class="mt-1 t-small">{{ $complaint->reporter?->name }} · {{ $complaint->created_at->translatedFormat('M d, Y') }}</p>
                        <a href="{{ route('admin.complaints.show', $complaint) }}" class="mt-2 inline-block text-[13px] font-bold text-ink hover:underline">{{ __('admin.open') }}</a>
                    </div>
                @empty
                    <p class="mt-3 t-muted">{{ __('admin.no_complaints') }}</p>
                @endforelse
            </x-card>

            <x-card>
                <h2 class="t-section">{{ __('admin.activity') }}</h2>
                @forelse ($activity as $entry)
                    @php($entryLabel = trans()->has('activity.'.$entry->action) ? __('activity.'.$entry->action) : \Illuminate\Support\Str::headline(\Illuminate\Support\Str::after($entry->action, '.')))
                    @php($entryLabel = $entry->action === 'request.status_changed' && filled($entry->metadata['status'] ?? null) ? __('activity.status_changed_to', ['status' => __('status.request.'.$entry->metadata['status'])]) : $entryLabel)
                    <div class="mt-3 flex items-start justify-between gap-4 border-t border-line pt-3 first:mt-2 first:border-0 first:pt-0">
                        <div>
                            <div class="font-semibold">{{ $entryLabel }}</div>
                            <div class="t-small">{{ $entry->user?->name ?? __('messages.none') }}</div>
                        </div>
                        <span class="t-small">{{ $entry->created_at->translatedFormat('M d, Y · H:i') }}</span>
                    </div>
                @empty
                    <p class="mt-3 t-muted">{{ __('admin.no_activity') }}</p>
                @endforelse
            </x-card>
        </div>

        <aside class="space-y-5">
            <x-card>
                <h3 class="t-card">{{ __('admin.customer') }}</h3>
                <p class="mt-2 t-muted">
                    {{ $r->customer->name }}<br>
                    {{ $r->customer->email }}<br>
                    {{ $r->customer->phone ?: __('messages.none') }}
                </p>
            </x-card>

            <x-card>
                <h3 class="t-card">{{ __('requests.appointment') }}</h3>
                <p class="mt-2 t-muted">
                    {{ __('admin.created') }}: {{ $r->created_at->translatedFormat('M d, Y') }} ({{ $r->created_at->diffForHumans() }})<br>
                    {{ __('bookings.preferred_date') }}: {{ optional($r->preferred_date)?->translatedFormat('M d, Y') ?? __('messages.none') }}<br>
                    {{ __('bookings.preferred_time') }}: {{ $r->preferred_time ?? __('messages.none') }}
                </p>
                @if ($r->address)
                    <p class="mt-3 t-small">
                        {{ $r->address->street }}, {{ $r->address->area }}, {{ $r->address->city }} {{ $r->address->postal_code }}
                        @if ($r->address->instructions)<br>{{ $r->address->instructions }}@endif
                    </p>
                @endif
            </x-card>

            <x-card>
                <h3 class="t-card">{{ __('admin.assigned_professional') }}</h3>
                @if ($r->worker)
                    <p class="mt-2 t-muted">
                        {{ $r->worker->name }}
                        @if ($profile && $profile->rating_count > 0)
                            · {{ number_format((float) $profile->rating_average, 1) }}★
                        @endif
                    </p>
                @else
                    <p class="mt-2 t-muted">{{ __('admin.unassigned') }}</p>
                @endif

                @if ($r->status === 'pending')
                    <form method="POST" action="{{ route('admin.requests.assign', $r) }}" class="mt-4 grid gap-2">
                        @csrf
                        <label class="sr-only" for="assign-worker">{{ __('admin.assign_professional') }}</label>
                        <select id="assign-worker" name="worker_id" class="input" @disabled($eligibleWorkers->isEmpty())>
                            <option value="">{{ __('admin.assign_professional') }}</option>
                            @forelse ($eligibleWorkers as $w)
                                <option value="{{ $w->id }}" @selected($r->worker_id === $w->id)>
                                    {{ $w->name }}@if ($w->workerProfile?->rating_count) · {{ number_format((float) $w->workerProfile->rating_average, 1) }}★ @endif
                                </option>
                            @empty
                                <option value="">{{ __('admin.no_qualified') }}</option>
                            @endforelse
                        </select>
                        <x-button type="submit" variant="secondary" :disabled="$eligibleWorkers->isEmpty()">
                            {{ $r->worker_id ? __('admin.update_assignment') : __('admin.assign') }}
                        </x-button>
                        <p class="t-small">{{ __('admin.assign_hint') }}</p>
                    </form>
                @endif
            </x-card>

            <x-card>
                <h3 class="t-card">{{ __('requests.payment') }}</h3>
                @if ($r->payment)
                    <div class="mt-2 flex flex-wrap items-center gap-1.5">
                        <x-status type="payment" :value="$r->payment->status" />
                        @if ($r->payment->refunds->firstWhere('status', 'completed'))
                            <x-badge tone="neutral">{{ __('payments.refunded') }}</x-badge>
                        @endif
                    </div>
                    <dl class="mt-3 space-y-1 text-sm">
                        <div class="flex justify-between"><dt class="t-muted">{{ __('payments.col_gross') }}</dt><dd class="tabular-nums"><x-money :amount="$r->payment->amount" /></dd></div>
                        <div class="flex justify-between"><dt class="t-muted">{{ __('payments.col_commission') }}</dt><dd class="tabular-nums"><x-money :amount="$r->payment->commission_amount" /></dd></div>
                        <div class="flex justify-between"><dt class="t-muted">{{ __('payments.col_net') }}</dt><dd class="tabular-nums"><x-money :amount="$r->payment->worker_net" /></dd></div>
                    </dl>
                    @if ($r->invoice)
                        <p class="mt-3 t-small">{{ __('payments.invoice_number') }}: {{ $r->invoice->invoice_number }}</p>
                    @endif
                @else
                    <p class="mt-2 t-muted">{{ __('messages.none') }}</p>
                @endif
            </x-card>
        </aside>
    </div>
</div>
@endsection
