@extends('layouts.app')
@section('content')
@php($title = $serviceRequest->reference)
@php($canWork = in_array($serviceRequest->status, ['accepted', 'scheduled', 'on_the_way', 'in_progress'], true))
@php($existingQuote = $serviceRequest->quotations->sortByDesc('id')->first())

<div class="container-app py-8 sm:py-10">
    <x-page-header
        :back="route('worker.jobs.index')"
        :backLabel="__('workers.back_to_jobs')"
        :eyebrow="$serviceRequest->reference"
        :title="$serviceRequest->category->localized_name">
        <x-slot:actions>
            <x-status type="request" :value="$serviceRequest->status === 'pending' ? 'awaiting_acceptance' : $serviceRequest->status" />
        </x-slot:actions>
    </x-page-header>

    @if ($serviceRequest->is_emergency)
        <x-emergency-flag variant="banner" class="mt-6" />
    @endif

    <div class="mt-8 grid gap-6 lg:grid-cols-[1.4fr_1fr]">
        <div class="space-y-5">
            <x-card>
                <h2 class="t-section">{{ __('workers.problem') }}</h2>
                <p class="mt-2 t-body whitespace-pre-line">{{ $serviceRequest->problem_description }}</p>
                <h3 class="mt-6 t-label">{{ __('workers.progress') }}</h3>
                <p class="mt-1 t-small">{{ __('workers.progress_hint.'.$serviceRequest->status) }}</p>
                <x-request-timeline :status="$serviceRequest->status" :history="$timeline" :worker="$serviceRequest->worker" class="mt-4" />
            </x-card>

            @if ($serviceRequest->status === 'pending')
                <x-card class="border-amber-200 bg-amber-50">
                    <p class="font-bold text-amber-900">{{ __('workers.assigned_to_you') }}</p>
                    <p class="mt-1 text-sm text-amber-800">{{ __('workers.assigned_to_you_hint') }}</p>
                    <div class="mt-4 flex flex-wrap gap-3">
                        <form method="POST" action="{{ route('worker.jobs.accept', $serviceRequest) }}">
                            @csrf<x-button>{{ __('workers.accept_job') }}</x-button>
                        </form>
                        <form method="POST" action="{{ route('worker.jobs.decline', $serviceRequest) }}" data-confirm="{{ __('messages.are_you_sure') }}">
                            @csrf<x-button variant="secondary">{{ __('workers.decline') }}</x-button>
                        </form>
                    </div>
                </x-card>
            @endif

            @if ($canWork)
                <x-card>
                    <h2 class="t-section">{{ __('workers.job_status') }}</h2>
                    <p class="mt-1 t-muted">{{ __('workers.job_status_hint') }}</p>
                    <form method="POST" enctype="multipart/form-data" action="{{ route('worker.jobs.status', $serviceRequest) }}" class="mt-5 grid gap-4 sm:grid-cols-2">
                        @csrf
                        <x-field :label="__('workers.next_action')" name="status" required>
                            <select id="f-status" name="status" class="input" required>
                                @foreach ($nextStatuses as $status)
                                    @if (in_array($status, ['scheduled', 'on_the_way', 'in_progress', 'completed'], true))
                                        <option value="{{ $status }}">{{ __('status.request.'.$status) }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </x-field>
                        <div class="hidden sm:block"></div>
                        <x-field :label="__('workers.date')" name="scheduled_date">
                            <input id="f-scheduled_date" type="date" name="scheduled_date" class="input" min="{{ now()->toDateString() }}"
                                   value="{{ $serviceRequest->appointment?->scheduled_date?->format('Y-m-d') }}">
                        </x-field>
                        <x-field :label="__('workers.time')" name="scheduled_time">
                            <input id="f-scheduled_time" type="time" name="scheduled_time" class="input"
                                   value="{{ $serviceRequest->appointment?->scheduled_time }}">
                        </x-field>
                        <x-field :label="__('workers.completion_notes')" name="completion_notes" class="sm:col-span-2">
                            <textarea id="f-completion_notes" name="completion_notes" class="input min-h-24"
                                      placeholder="{{ __('workers.completion_notes_placeholder') }}">{{ old('completion_notes') }}</textarea>
                        </x-field>
                        <x-field :label="__('workers.completion_images')" name="completion_images.0" class="sm:col-span-2">
                            <input type="file" name="completion_images[]" accept="image/jpeg,image/png,image/webp" multiple class="input">
                        </x-field>
                        <x-button class="sm:col-span-2">{{ __('workers.save_status') }}</x-button>
                    </form>
                </x-card>

                <x-card>
                    <h2 class="t-section">{{ $existingQuote ? __('workers.revise_quotation') : __('workers.submit_quotation') }}</h2>

                    @if ($existingQuote)
                        <div class="mt-3 rounded-xl border border-line bg-paper p-4 text-sm">
                            <div class="flex items-center justify-between gap-4">
                                <span class="t-muted">{{ __('workers.current_quotation') }}</span>
                                <span class="font-black tabular-nums"><x-money :amount="$existingQuote->total" /></span>
                            </div>
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <x-status type="quote" :value="$existingQuote->status" />
                                <span class="t-small">{{ __('workers.quotation_submitted_at') }}: <x-date :value="$existingQuote->created_at" /></span>
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('worker.jobs.quotation', $serviceRequest) }}"
                          class="mt-5 grid gap-4 sm:grid-cols-2"
                          x-data="{
                              q: {
                                  base: {{ (float) old('base_price', 0) }},
                                  additional: {{ (float) old('additional_charges', 0) }},
                                  discount: {{ (float) old('discount', 0) }},
                                  tax: {{ (float) old('tax', 0) }}
                              },
                              get preview() {
                                  const t = Math.max(0, (this.q.base || 0) + (this.q.additional || 0) - (this.q.discount || 0) + (this.q.tax || 0));
                                  return '$' + t.toFixed(2);
                              }
                          }">
                        @csrf
                        <x-field :label="__('workers.base_price')" name="base_price" required>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 grid place-items-center t-small ps-3">$</span>
                                <input id="f-base_price" class="input ps-7" name="base_price" type="number" step="0.01" min="0"
                                       value="{{ old('base_price') }}" required
                                       x-on:input="q.base = parseFloat($event.target.value) || 0">
                            </div>
                        </x-field>
                        <x-field :label="__('workers.additional_charges')" name="additional_charges">
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 grid place-items-center t-small ps-3">$</span>
                                <input id="f-additional_charges" class="input ps-7" name="additional_charges" type="number" step="0.01" min="0"
                                       value="{{ old('additional_charges') }}"
                                       x-on:input="q.additional = parseFloat($event.target.value) || 0">
                            </div>
                        </x-field>
                        <x-field :label="__('workers.discount_amount')" name="discount">
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 grid place-items-center t-small ps-3">$</span>
                                <input id="f-discount" class="input ps-7" name="discount" type="number" step="0.01" min="0"
                                       value="{{ old('discount') }}"
                                       x-on:input="q.discount = parseFloat($event.target.value) || 0">
                            </div>
                        </x-field>
                        <x-field :label="__('workers.tax_amount')" name="tax">
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 grid place-items-center t-small ps-3">$</span>
                                <input id="f-tax" class="input ps-7" name="tax" type="number" step="0.01" min="0"
                                       value="{{ old('tax') }}"
                                       x-on:input="q.tax = parseFloat($event.target.value) || 0">
                            </div>
                        </x-field>
                        <x-field :label="__('workers.quotation_notes')" name="notes" class="sm:col-span-2">
                            <textarea id="f-notes" class="input" name="notes">{{ old('notes') }}</textarea>
                        </x-field>

                        <div class="flex items-center justify-between gap-4 rounded-xl bg-paper px-4 py-3 sm:col-span-2">
                            <span class="t-label">{{ __('workers.quote_preview_label') }}</span>
                            <span class="text-lg font-black tabular-nums" x-text="preview">$0.00</span>
                        </div>

                        <x-button variant="accent" class="sm:col-span-2">
                            {{ $existingQuote ? __('workers.send_revision') : __('workers.send_quotation') }}
                        </x-button>
                    </form>
                </x-card>
            @endif
        </div>

        <aside class="space-y-5">
            <x-button :href="route('worker.messages.show', $serviceRequest)" variant="secondary" class="w-full">
                <x-icon name="chat" class="h-4 w-4" />{{ __('workers.message_customer') }}
            </x-button>
            <x-card>
                <h3 class="t-card">{{ __('workers.customer') }}</h3>
                <p class="mt-3 font-bold">{{ $serviceRequest->customer->name }}</p>
                <p class="mt-1 t-muted">
                    @if ($serviceRequest->customer->phone)
                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $serviceRequest->customer->phone) }}"
                           class="font-semibold text-ink underline-offset-2 hover:underline">{{ $serviceRequest->customer->phone }}</a>
                    @else
                        {{ __('workers.no_phone') }}
                    @endif
                </p>
                <p class="mt-1 t-muted">{{ $serviceRequest->address?->street }}, {{ $serviceRequest->address?->city }}</p>
                @if ($serviceRequest->customer->phone)
                    <x-whatsapp-button :phone="$serviceRequest->customer->phone"
                                       :context="$serviceRequest->category->localized_name"
                                       :label="__('workers.whatsapp_customer')"
                                       class="mt-4 w-full" />
                @endif
            </x-card>
            <x-card>
                <h3 class="t-card">{{ __('workers.appointment') }}</h3>
                <p class="mt-3 t-muted">
                    {{ $serviceRequest->appointment?->scheduled_date?->translatedFormat('M d, Y') ?? __('workers.not_scheduled') }}<br>
                    {{ $serviceRequest->appointment?->scheduled_time
                        ? \Illuminate\Support\Str::of((string) $serviceRequest->appointment->scheduled_time)->substr(0, 5)
                        : __('messages.none') }}
                </p>
            </x-card>

            {{--
                Customer-proposed reschedule (App\Http\Controllers\Worker\RescheduleResponseController).
                Purely additive — the existing scheduling controls in the job-status
                form above are unaffected and remain the worker's own way to set or
                change the appointment unilaterally.
            --}}
            @php($pendingReschedule = $serviceRequest->status === 'scheduled' ? $serviceRequest->rescheduleRequests->firstWhere('status', 'pending') : null)
            @if ($pendingReschedule)
                <x-card>
                    <h3 class="t-card">{{ __('workers.reschedule_pending_title') }}</h3>
                    <dl class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="t-muted">{{ __('workers.reschedule_current') }}</dt>
                            <dd class="text-end font-semibold">
                                {{ $serviceRequest->appointment?->scheduled_date?->translatedFormat('M d, Y') ?? __('workers.not_scheduled') }}
                                @if ($serviceRequest->appointment?->scheduled_time)
                                    · {{ \Illuminate\Support\Str::of((string) $serviceRequest->appointment->scheduled_time)->substr(0, 5) }}
                                @endif
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4 border-t border-line pt-2">
                            <dt class="t-muted">{{ __('workers.reschedule_proposed') }}</dt>
                            <dd class="text-end font-semibold">
                                {{ $pendingReschedule->proposed_date->translatedFormat('M d, Y') }}
                                · {{ \Illuminate\Support\Str::of((string) $pendingReschedule->proposed_time)->substr(0, 5) }}
                            </dd>
                        </div>
                    </dl>

                    @if ($pendingReschedule->reason)
                        <div class="mt-4 rounded-xl bg-paper p-3">
                            <p class="t-eyebrow">{{ __('workers.reschedule_customer_reason') }}</p>
                            <p class="mt-1 t-small whitespace-pre-line">{{ $pendingReschedule->reason }}</p>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('worker.reschedule.accept', $pendingReschedule) }}" class="mt-4">
                        @csrf
                        <x-button variant="accent" size="sm" class="w-full">{{ __('workers.reschedule_accept') }}</x-button>
                    </form>

                    <form method="POST" action="{{ route('worker.reschedule.decline', $pendingReschedule) }}" class="mt-2 grid gap-2">
                        @csrf
                        <x-field :label="__('workers.reschedule_decline_reason')" name="decline_reason">
                            <textarea id="f-decline_reason" name="decline_reason" class="input min-h-16" maxlength="1000"
                                      placeholder="{{ __('workers.reschedule_decline_reason_placeholder') }}">{{ old('decline_reason') }}</textarea>
                        </x-field>
                        <x-button variant="secondary" size="sm" class="w-full">{{ __('workers.reschedule_decline') }}</x-button>
                    </form>
                </x-card>
            @endif

            <x-card>
                <h3 class="t-card">{{ __('workers.payment_context') }}</h3>
                <div class="mt-3 flex items-center justify-between gap-3">
                    <x-status type="payment" :value="$serviceRequest->payment_status === 'paid' ? 'paid' : 'pending'" />
                    @if ($serviceRequest->lockedAmount())
                        <span class="font-black tabular-nums"><x-money :amount="$serviceRequest->lockedAmount()" /></span>
                    @endif
                </div>
            </x-card>
        </aside>
    </div>
</div>
@endsection
