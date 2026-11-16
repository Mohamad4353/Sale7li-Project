@extends('layouts.app')
@section('content')
@php($title = $serviceRequest->reference)
@php($profile = $serviceRequest->worker?->workerProfile)
@php($statusKey = $serviceRequest->worker_id && $serviceRequest->status === 'pending' ? 'assigned' : $serviceRequest->status)

<div class="container-app py-8 sm:py-10">
    <x-page-header
        :back="route('customer.requests.index')"
        :backLabel="__('requests.back_to_requests')"
        :eyebrow="$serviceRequest->reference"
        :title="$serviceRequest->category->localized_name">
        <x-slot:actions>
            <x-status type="request" :value="$serviceRequest->worker_id && $serviceRequest->status === 'pending' ? 'awaiting_acceptance' : $serviceRequest->status" />
        </x-slot:actions>
    </x-page-header>

    @if ($serviceRequest->is_emergency)
        <x-emergency-flag variant="banner" class="mt-6" />
    @endif

    <div class="mt-8 grid gap-6 lg:grid-cols-[1.6fr_1fr]">
        <div class="space-y-6">
            {{-- 1 · Current progress --}}
            <x-card>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <span class="t-eyebrow">{{ __('requests.live_status') }}</span>
                    <x-status type="request" :value="$serviceRequest->worker_id && $serviceRequest->status === 'pending' ? 'awaiting_acceptance' : $serviceRequest->status" />
                </div>
                <p class="mt-3 t-body">{{ __('requests.progress_hint.'.$statusKey) }}</p>

                @if ($serviceRequest->appointment && in_array($serviceRequest->status, ['scheduled', 'on_the_way'], true))
                    <p class="mt-2 flex items-center gap-1.5 t-small">
                        <x-icon name="calendar" class="h-4 w-4 shrink-0" />
                        {{ $serviceRequest->appointment->scheduled_date?->translatedFormat('l, M j') }}
                        · {{ \Illuminate\Support\Str::of((string) $serviceRequest->appointment->scheduled_time)->substr(0, 5) }}
                    </p>
                @endif

                @if ($serviceRequest->worker)
                    <x-button :href="route('customer.messages.show', $serviceRequest)" variant="secondary" size="sm" class="mt-4">
                        <x-icon name="chat" class="h-4 w-4" />{{ __('requests.message') }}
                    </x-button>
                @endif
            </x-card>

            {{-- 2 · Visual timeline --}}
            <x-card>
                <div class="flex items-center justify-between gap-4">
                    <h2 class="t-section">{{ __('requests.timeline') }}</h2>
                    <x-badge tone="success" dot>{{ __('requests.live_status') }}</x-badge>
                </div>
                <p class="mt-1 t-muted">{{ __('requests.timeline_sub') }}</p>
                <x-request-timeline :status="$serviceRequest->status" :history="$timeline" :worker="$serviceRequest->worker" class="mt-6" />

                @if ($serviceRequest->worker)
                    <div class="mt-8 rounded-2xl bg-paper p-5">
                        <p class="t-eyebrow">{{ __('requests.your_professional') }}</p>
                        <div class="mt-3 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                            <div class="flex items-center gap-3">
                                <x-avatar :name="$serviceRequest->worker->name" />
                                <div>
                                    <div class="font-extrabold">{{ $serviceRequest->worker->name }}</div>
                                    <div class="t-small flex items-center gap-2">
                                        <x-rating :average="$profile?->rating_average ?? 0" :count="$profile?->rating_count ?? 0" />
                                        @if ($profile && $profile->rating_count > 0)
                                            <span>· {{ $profile->completed_jobs }} {{ __('requests.completed_jobs') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <x-button :href="route('customer.messages.show', $serviceRequest)" variant="secondary" size="sm">
                                <x-icon name="chat" class="h-4 w-4" />{{ __('requests.message') }}
                            </x-button>
                        </div>
                        @if ($serviceRequest->status === 'pending')
                            <p class="mt-3 t-small">{{ __('requests.awaiting_confirmation') }}</p>
                        @endif
                    </div>
                @endif
            </x-card>

            {{-- 3 · Request summary --}}
            <x-card>
                <h2 class="t-section">{{ __('requests.problem') }}</h2>
                <p class="mt-2 t-body whitespace-pre-line">{{ $serviceRequest->problem_description }}</p>

                <h3 class="mt-6 t-label">{{ __('requests.attachments') }}</h3>
                @if (!empty($serviceRequest->problem_images))
                    <div class="mt-2 flex flex-wrap gap-3">
                        @foreach ($serviceRequest->problem_images as $i => $path)
                            <a href="{{ route('attachments.request', [$serviceRequest, 'problem', $i]) }}" target="_blank" rel="noopener"
                               class="grid h-20 w-20 place-items-center overflow-hidden rounded-xl border border-line bg-paper">
                                <img src="{{ route('attachments.request', [$serviceRequest, 'problem', $i]) }}"
                                 alt="{{ __('requests.attachment_number', ['number' => $i + 1]) }}"
                                 width="80" height="80" loading="lazy" decoding="async" class="h-full w-full object-cover">
                            </a>
                        @endforeach
                    </div>
                @else
                    <p class="mt-1 t-small">{{ __('requests.no_attachments') }}</p>
                @endif

                <dl class="mt-6 grid gap-x-6 gap-y-3 border-t border-line pt-5 sm:grid-cols-2">
                    <div>
                        <dt class="t-eyebrow">{{ __('requests.service_category') }}</dt>
                        <dd class="mt-0.5 font-semibold">{{ $serviceRequest->category->localized_name }}</dd>
                    </div>
                    <div>
                        <dt class="t-eyebrow">{{ __('requests.service_address') }}</dt>
                        <dd class="mt-0.5 font-semibold">{{ collect([$serviceRequest->address?->street, $serviceRequest->address?->city])->filter()->join(', ') ?: __('messages.none') }}</dd>
                    </div>
                    <div>
                        <dt class="t-eyebrow">{{ __('requests.preferred_time_label') }}</dt>
                        <dd class="mt-0.5 font-semibold">{{ optional($serviceRequest->preferred_date)?->translatedFormat('M j, Y') ?? __('requests.pending_schedule') }} · {{ $serviceRequest->preferred_time ?? __('messages.none') }}</dd>
                    </div>
                    <div>
                        <dt class="t-eyebrow">{{ __('requests.reference') }}</dt>
                        <dd class="mt-0.5 font-semibold tabular-nums">{{ $serviceRequest->reference }}</dd>
                    </div>
                </dl>
            </x-card>

            {{--
                @js() does not compile inside a Blade component tag's plain
                attribute value (only {{ }} echoes are compiled there) — it was
                previously left as a literal, uncompiled expression in the
                x-text below. Fixed the same way as the reschedule toggle
                further down this page: pre-compute the JSON-safe strings and
                echo them via {{ }}, which produces byte-identical output to
                @js() and does compile in this position.
            --}}
            @php($reportCloseLabel = \Illuminate\Support\Js::from(__('messages.close')))
            @php($reportOpenLabel = \Illuminate\Support\Js::from(__('complaints.report_title')))
            <x-card x-data="{ open: {{ $errors->hasAny(['category', 'description']) ? 'true' : 'false' }} }">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="t-section">{{ __('complaints.report_title') }}</h2>
                        <p class="mt-1 t-muted">{{ __('complaints.report_hint') }}</p>
                    </div>
                    <x-button type="button" variant="secondary" size="sm"
                              x-on:click="open = !open" x-bind:aria-expanded="open" aria-controls="report-problem-form"
                              x-text="open ? {{ $reportCloseLabel }} : {{ $reportOpenLabel }}"></x-button>
                </div>

                <form id="report-problem-form" x-show="open" x-cloak method="POST" action="{{ route('customer.complaints.store', $serviceRequest) }}" class="mt-5 grid gap-4">
                    @csrf
                    <x-field :label="__('complaints.category')" name="category" required>
                        <select id="f-category" name="category" class="input" required>
                            <option value="">{{ __('complaints.select_category') }}</option>
                            @foreach (['work_quality', 'no_show', 'damage', 'billing', 'behavior', 'other'] as $c)
                                <option value="{{ $c }}" @selected(old('category') === $c)>{{ __('complaints.categories.'.$c) }}</option>
                            @endforeach
                        </select>
                    </x-field>
                    <x-field :label="__('complaints.description')" name="description" required>
                        <textarea id="f-description" name="description" class="input min-h-28" maxlength="3000"
                                  placeholder="{{ __('complaints.description_placeholder') }}" required>{{ old('description') }}</textarea>
                    </x-field>
                    <x-button variant="accent">{{ __('complaints.submit') }}</x-button>
                </form>
            </x-card>

            @if ($serviceRequest->status === 'completed')
                <x-card>
                    <h2 class="t-section">{{ __('requests.review') }}</h2>
                    @if ($serviceRequest->review)
                        <div class="mt-3 rounded-2xl bg-paper p-5">
                            <x-rating :value="$serviceRequest->review->rating" size="md" />
                            @if ($serviceRequest->review->comment)
                                <p class="mt-2 t-muted">{{ $serviceRequest->review->comment }}</p>
                            @endif
                        </div>
                    @else
                        <form method="POST" action="{{ route('customer.reviews.store', $serviceRequest) }}" class="mt-4 grid gap-4">
                            @csrf
                            <x-field :label="__('requests.rating')" name="rating" required>
                                <select id="f-rating" name="rating" class="input" required>
                                    <option value="">{{ __('requests.select_rating') }}</option>
                                    @for ($i = 5; $i >= 1; $i--)
                                        <option value="{{ $i }}" @selected(old('rating') == $i)>{{ $i }} — {{ __('reviews.scale.'.$i) }}</option>
                                    @endfor
                                </select>
                            </x-field>
                            <x-field :label="__('requests.comment')" name="comment">
                                <textarea id="f-comment" name="comment" class="input min-h-24" maxlength="2000">{{ old('comment') }}</textarea>
                            </x-field>
                            <x-button variant="accent">{{ __('requests.submit_review') }}</x-button>
                        </form>
                    @endif

                    <x-button :href="route('customer.requests.rebook', $serviceRequest)" variant="secondary" class="mt-4 w-full">
                        {{ __('requests.book_again') }}
                    </x-button>
                </x-card>
            @endif
        </div>

        <aside class="space-y-5">
            <x-card>
                <h3 class="t-card">{{ __('requests.appointment') }}</h3>
                <p class="mt-3 t-muted">
                    {{-- Only a real, worker-confirmed Appointment is shown here — never
                         the customer's own preferred_date/preferred_time, which is not
                         yet confirmed and must not be presented as an appointment. --}}
                    @if ($serviceRequest->appointment)
                        {{ $serviceRequest->appointment->scheduled_date?->translatedFormat('M d, Y') }}<br>
                        {{ \Illuminate\Support\Str::of((string) $serviceRequest->appointment->scheduled_time)->substr(0, 5) }}
                    @else
                        {{ __('requests.pending_schedule') }}
                    @endif
                </p>

                {{--
                    Customer-proposed reschedule. This never appears in the
                    status timeline above (ServiceRequest.status never
                    changes) — it is a self-contained side proposal, shown only
                    on this existing Appointment card.
                --}}
                @php($pendingReschedule = $serviceRequest->rescheduleRequests->firstWhere('status', 'pending'))
                @php($latestReschedule = $serviceRequest->rescheduleRequests->sortByDesc('id')->first())

                @if ($pendingReschedule)
                    <div class="mt-4 rounded-xl bg-paper p-4">
                        <p class="t-eyebrow">{{ __('requests.reschedule_pending_title') }}</p>
                        <p class="mt-1 t-small font-semibold">
                            {{ $pendingReschedule->proposed_date->translatedFormat('M d, Y') }}
                            · {{ \Illuminate\Support\Str::of((string) $pendingReschedule->proposed_time)->substr(0, 5) }}
                        </p>
                        <p class="mt-1 t-small">{{ __('requests.reschedule_pending_hint') }}</p>
                    </div>
                @elseif ($latestReschedule?->status === 'declined')
                    <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 p-4">
                        <p class="t-eyebrow text-rose-700">{{ __('requests.reschedule_declined_title') }}</p>
                        <p class="mt-1 t-small text-rose-700/90">{{ __('requests.reschedule_declined_hint') }}</p>
                        @if ($latestReschedule->decline_reason)
                            <p class="mt-1 t-small text-rose-700/90">{{ $latestReschedule->decline_reason }}</p>
                        @endif
                    </div>
                @elseif ($latestReschedule?->status === 'superseded')
                    <div class="mt-4 rounded-xl bg-paper p-4">
                        <p class="t-eyebrow">{{ __('requests.reschedule_superseded_title') }}</p>
                        <p class="mt-1 t-small">{{ __('requests.reschedule_superseded_hint') }}</p>
                    </div>
                @elseif ($serviceRequest->status === 'scheduled')
                    @php($rescheduleOpenLabel = \Illuminate\Support\Js::from(__('messages.close')))
                    @php($rescheduleClosedLabel = \Illuminate\Support\Js::from(__('requests.reschedule_cta')))
                    <div class="mt-4" x-data="{ open: {{ $errors->hasAny(['proposed_date', 'proposed_time', 'reason']) ? 'true' : 'false' }} }">
                        <x-button type="button" variant="secondary" size="sm" class="w-full"
                                  x-on:click="open = !open" x-bind:aria-expanded="open" aria-controls="reschedule-form"
                                  x-text="open ? {{ $rescheduleOpenLabel }} : {{ $rescheduleClosedLabel }}">
                            {{ __('requests.reschedule_cta') }}
                        </x-button>

                        <form id="reschedule-form" x-show="open" x-cloak method="POST" action="{{ route('customer.requests.reschedule', $serviceRequest) }}" class="mt-4 grid gap-4">
                            @csrf
                            <p class="t-small">{{ __('requests.reschedule_hint') }}</p>
                            <x-field :label="__('requests.reschedule_proposed_date')" name="proposed_date" required>
                                <input id="f-proposed_date" type="date" name="proposed_date" min="{{ now()->toDateString() }}" value="{{ old('proposed_date') }}" class="input" required>
                            </x-field>
                            <x-field :label="__('requests.reschedule_proposed_time')" name="proposed_time" required>
                                <input id="f-proposed_time" type="time" name="proposed_time" value="{{ old('proposed_time') }}" class="input" required>
                            </x-field>
                            <x-field :label="__('requests.reschedule_reason')" name="reason">
                                <textarea id="f-reason" name="reason" class="input min-h-20" maxlength="1000"
                                          placeholder="{{ __('requests.reschedule_reason_placeholder') }}">{{ old('reason') }}</textarea>
                            </x-field>
                            <x-button variant="accent">{{ __('requests.reschedule_submit') }}</x-button>
                        </form>
                    </div>
                @endif
            </x-card>

            <x-card>
                <h3 class="t-card">{{ __('requests.quotation') }}</h3>
                @forelse ($serviceRequest->quotations as $q)
                    <div class="mt-4 border-t border-line pt-4 first:mt-3 first:border-0 first:pt-0">
                        <dl class="space-y-1.5 text-sm">
                            <div class="flex justify-between gap-4">
                                <dt class="t-muted">{{ __('requests.quote_base') }}</dt>
                                <dd class="tabular-nums"><x-money :amount="$q->base_price" /></dd>
                            </div>
                            @if ((float) $q->additional_charges > 0)
                                <div class="flex justify-between gap-4">
                                    <dt class="t-muted">{{ __('requests.quote_additional') }}</dt>
                                    <dd class="tabular-nums"><x-money :amount="$q->additional_charges" /></dd>
                                </div>
                            @endif
                            @if ((float) $q->discount > 0)
                                <div class="flex justify-between gap-4">
                                    <dt class="t-muted">{{ __('requests.quote_discount') }}</dt>
                                    <dd class="tabular-nums">−<x-money :amount="$q->discount" /></dd>
                                </div>
                            @endif
                            @if ((float) $q->tax > 0)
                                <div class="flex justify-between gap-4">
                                    <dt class="t-muted">{{ __('requests.quote_tax') }}</dt>
                                    <dd class="tabular-nums"><x-money :amount="$q->tax" /></dd>
                                </div>
                            @endif
                            <div class="flex justify-between gap-4 border-t border-line pt-1.5 font-black">
                                <dt>{{ __('requests.quotation_total') }}</dt>
                                <dd class="tabular-nums"><x-money :amount="$q->total" /></dd>
                            </div>
                        </dl>

                        <div class="mt-3"><x-status type="quote" :value="$q->status" /></div>

                        @if ($q->notes)
                            <div class="mt-3 rounded-xl bg-paper p-3">
                                <p class="t-eyebrow">{{ __('requests.quote_notes') }}</p>
                                <p class="mt-1 t-small whitespace-pre-line">{{ $q->notes }}</p>
                            </div>
                        @endif

                        @if ($q->status === 'pending')
                            <div class="mt-4 flex gap-2">
                                <form method="POST" action="{{ route('customer.quotations.accept', $q) }}"
                                      data-confirm="{{ __('requests.accept_confirm', ['amount' => \App\Payments\Money::format($q->total)]) }}">
                                    @csrf<x-button variant="accent" size="sm">{{ __('requests.accept') }}</x-button>
                                </form>
                                <form method="POST" action="{{ route('customer.quotations.reject', $q) }}">
                                    @csrf<x-button variant="secondary" size="sm">{{ __('requests.reject') }}</x-button>
                                </form>
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="mt-3 t-muted">{{ __('requests.quotation_none') }}</p>
                @endforelse
            </x-card>

            @php($pmt = $serviceRequest->payment)
            @if ($serviceRequest->payment_status === 'paid')
                @php($paymentRefund = $serviceRequest->payment?->refunds->firstWhere('status', 'completed'))
                <x-card>
                    <h3 class="t-card">{{ __('requests.payment') }}</h3>
                    <div class="mt-3 flex flex-wrap items-center gap-1.5">
                        <x-status type="payment" value="paid" />
                        @if ($pmt && $pmt->payment_method === 'whish')
                            <x-badge tone="neutral">{{ __('payments.method_whish') }}</x-badge>
                        @endif
                        @if ($paymentRefund)
                            <x-badge tone="neutral" dot>{{ __('status.payment.refunded') }}</x-badge>
                        @endif
                    </div>
                    @if ($paymentRefund)
                        <p class="mt-3 t-small">{{ __('payments.refunded_notice_body', [
                            'amount' => \App\Payments\Money::format($paymentRefund->amount),
                            'date' => $paymentRefund->processed_at?->translatedFormat('M j, Y'),
                        ]) }}</p>
                    @endif
                    @if ($serviceRequest->invoice)
                        <x-button :href="route('customer.requests.invoice', $serviceRequest)" variant="secondary" size="sm" class="mt-4 w-full">
                            {{ __('payments.view_invoice') }}
                        </x-button>
                    @endif
                </x-card>
            @elseif ($pmt && $pmt->isWhishPending())
                <x-card>
                    <h3 class="t-card">{{ __('requests.payment') }}</h3>
                    <div class="mt-3"><x-status type="payment" value="pending" /></div>
                    <p class="mt-3 t-small">{{ __('payments.whish_pending_body', ['reference' => $pmt->whish_reference]) }}</p>
                </x-card>
            @elseif ($serviceRequest->quoted_total)
                <x-card>
                    <h3 class="t-card">{{ __('requests.payment') }}</h3>

                    @if ($pmt && $pmt->payment_method === 'whish' && $pmt->status === 'failed')
                        <div class="mt-3">
                            <x-alert tone="danger" :title="__('payments.whish_rejected_title')">
                                {{ $pmt->rejection_reason
                                    ? __('payments.whish_rejected_reason', ['reason' => $pmt->rejection_reason])
                                    : __('payments.whish_rejected_no_reason') }}
                            </x-alert>
                        </div>
                    @endif

                    <div class="mt-3 flex items-center justify-between border-b border-line pb-3">
                        <span class="t-muted">{{ __('payments.amount_due') }}</span>
                        <span class="text-lg font-black tabular-nums"><x-money :amount="$serviceRequest->quoted_total" /></span>
                    </div>

                    <div class="mt-4">
                        <p class="t-label">{{ __('payments.pay_by_card') }}</p>
                        <p class="mt-1 t-small">{{ __('payments.card_note') }}</p>
                        <form method="POST" action="{{ route('customer.payments.pay', $serviceRequest) }}" class="mt-3"
                              data-confirm="{{ __('payments.pay_confirm', ['amount' => \App\Payments\Money::format($serviceRequest->quoted_total)]) }}">
                            @csrf
                            <x-button class="w-full">{{ __('payments.pay_securely') }}</x-button>
                        </form>
                    </div>

                    <div class="mt-4 border-t border-line pt-4">
                        <p class="t-label">{{ __('payments.method_whish') }}</p>
                        <x-button :href="route('customer.payments.whish', $serviceRequest)" variant="secondary" class="mt-2 w-full">
                            {{ __('payments.pay_with_whish') }}
                        </x-button>
                    </div>
                </x-card>
            @endif

            @if (in_array($serviceRequest->status, ['pending', 'accepted', 'scheduled'], true))
                <x-card>
                    <form method="POST" action="{{ route('customer.requests.cancel', $serviceRequest) }}" data-confirm="{{ __('messages.are_you_sure') }}" class="grid gap-3">
                        @csrf
                        <x-field :label="__('requests.cancel_reason_label')" name="reason">
                            <select id="f-reason" name="reason" class="input">
                                <option value="">{{ __('requests.cancel_reason_none') }}</option>
                                @foreach (['no_longer_needed', 'found_another_provider', 'schedule_changed', 'created_by_mistake', 'other'] as $reason)
                                    <option value="{{ $reason }}" @selected(old('reason') === $reason)>{{ __('requests.cancel_reasons.'.$reason) }}</option>
                                @endforeach
                            </select>
                        </x-field>
                        <x-button variant="danger" class="w-full">{{ __('requests.cancel_request') }}</x-button>
                    </form>
                </x-card>
            @endif
        </aside>
    </div>
</div>
@endsection
