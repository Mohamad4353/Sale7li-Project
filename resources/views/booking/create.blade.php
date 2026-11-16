@extends('layouts.app')
@section('content')
@php($title = __('bookings.title'))

<div class="container-app max-w-3xl py-8 sm:py-10">
    <x-page-header :eyebrow="__('bookings.eyebrow')" :title="__('bookings.title')" :subtitle="__('bookings.sub')" />

    <x-booking-next-steps class="mt-8" />

    {{--
        Advisory AI Service Assistant. Deliberately OUTSIDE the booking <form>
        so nothing here can ever become part of the multipart submission — it
        only ever writes into the existing category radios / description
        textarea below via plain DOM APIs (never innerHTML, never a new field).
        See resources/js/app.js `window.aiSuggestion` and
        Customer\AiSuggestionController.
    --}}
    {{--
        @js() cannot be used directly inside a Blade component tag's plain
        attribute value (only {{ }} echoes are compiled there) — so the
        JSON-safe URL is built once here and echoed, giving byte-identical
        output to @js() without the directive being silently left uncompiled.
    --}}
    @php($aiSuggestionUrl = \Illuminate\Support\Js::from(route('customer.booking.aiSuggestion')))
    <x-card class="mt-6" x-data="aiSuggestion({{ $aiSuggestionUrl }})">
        <h2 class="t-card">{{ __('ai_assistant.title') }}</h2>
        <p class="mt-1 t-small">{{ __('ai_assistant.prompt') }}</p>

        <label for="ai-assistant-description" class="field-label mt-4">{{ __('ai_assistant.textarea_label') }}</label>
        <textarea id="ai-assistant-description" x-model="description" :disabled="state === 'loading'"
                  class="input mt-1.5 min-h-24" placeholder="{{ __('ai_assistant.textarea_placeholder') }}"></textarea>

        <div class="mt-3">
            <x-button type="button" id="ai-assistant-analyze" variant="secondary" size="sm" @click="request()"
                      x-bind:disabled="state === 'loading' || description.trim() === ''"
                      x-bind:aria-busy="(state === 'loading').toString()">
                <span x-show="state !== 'loading'">{{ __('ai_assistant.action') }}</span>
                <span x-show="state === 'loading'" x-cloak>{{ __('ai_assistant.loading') }}</span>
            </x-button>
        </div>

        <div aria-live="polite" class="mt-4">
            <template x-if="state === 'success' && suggestion">
                <div class="rounded-xl border border-line bg-surface-2 p-4">
                    <p class="t-eyebrow">{{ __('ai_assistant.suggestion_badge') }}</p>
                    <h3 class="mt-1 t-card">{{ __('ai_assistant.suggestion_heading') }}</h3>
                    <p class="mt-2 t-small" x-show="suggestion.label" x-text="@js(__('ai_assistant.suggested_service')) + ': ' + suggestion.label"></p>
                    <p class="mt-2 t-body" x-text="suggestion.description"></p>
                    <p class="mt-3 t-small">{{ __('ai_assistant.disclaimer') }}</p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <x-button type="button" size="sm" @click="apply()">{{ __('ai_assistant.use_suggestion') }}</x-button>
                        <x-button type="button" variant="secondary" size="sm" @click="dismiss()">{{ __('ai_assistant.dismiss') }}</x-button>
                    </div>
                </div>
            </template>

            <p role="alert" class="t-small text-muted" x-show="state === 'unavailable'" x-cloak>{{ __('ai_assistant.unavailable') }}</p>
            <p role="alert" class="t-small text-rose-600" x-show="state === 'error'" x-cloak>{{ __('ai_assistant.error') }}</p>
        </div>
    </x-card>

    <form method="POST" action="{{ route('customer.booking.store') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
        @csrf

        <x-card>
            <div class="flex items-center gap-3">
                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-ink text-xs font-bold text-white">1</span>
                <div>
                    <h2 class="t-card">{{ __('bookings.step_service') }}</h2>
                    <p class="t-small">{{ __('bookings.step_service_hint') }}</p>
                </div>
            </div>

            @php($preselectedCategory = $preselected ? $categories->firstWhere('id', (int) $preselected) : null)
            @if ($preselectedCategory)
                <p class="mt-4 rounded-xl bg-surface-2 px-4 py-3 t-small">
                    {{ __('bookings.preselected_note', ['name' => $preselectedCategory->localized_name]) }}
                </p>
            @endif

            <x-field name="service_category_id" class="mt-5">
                <fieldset class="min-w-0">
                    <legend class="sr-only">{{ __('bookings.step_service') }}</legend>
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($categories as $c)
                            <label class="cursor-pointer rounded-2xl border border-line p-4 transition hover:bg-surface-2 has-[:checked]:border-ink has-[:checked]:bg-surface-2">
                                <input class="me-2" type="radio" name="service_category_id" value="{{ $c->id }}" @checked(old('service_category_id', $preselected ?? null) == $c->id) required data-category-option data-slug="{{ $c->slug }}">
                                <span class="font-bold">{{ $c->icon }} {{ $c->localized_name }}</span>
                                <span class="mt-1 block t-small">{{ __('bookings.from') }} <x-money :amount="$c->base_price" /></span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            </x-field>
        </x-card>

        <x-card>
            <div class="flex items-center gap-3">
                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-ink text-xs font-bold text-white">2</span>
                <div>
                    <h2 class="t-card">{{ __('bookings.step_problem') }}</h2>
                    <p class="t-small">{{ __('bookings.step_problem_hint') }}</p>
                </div>
            </div>
            <x-field name="problem_description" class="mt-5" required>
                <textarea id="f-problem_description" name="problem_description" class="input min-h-36"
                          placeholder="{{ __('bookings.problem_placeholder') }}" required>{{ old('problem_description', $prefill['problem_description'] ?? null) }}</textarea>
            </x-field>
            <label class="mt-4 flex cursor-pointer items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm font-semibold text-amber-900">
                <input type="checkbox" name="is_emergency" value="1" @checked(old('is_emergency', $prefill['is_emergency'] ?? false)) class="rounded border-amber-300">
                {{ __('bookings.emergency') }}
            </label>
        </x-card>

        <x-card>
            <div class="flex items-center gap-3">
                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-ink text-xs font-bold text-white">3</span>
                <div>
                    <h2 class="t-card">{{ __('bookings.step_photos') }}</h2>
                    <p class="t-small">{{ __('bookings.step_photos_hint') }}</p>
                </div>
            </div>
            <x-field name="photos.0" class="mt-5">
                <input class="input" type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple>
            </x-field>
        </x-card>

        <x-card>
            <div class="flex items-center gap-3">
                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-ink text-xs font-bold text-white">4</span>
                <div>
                    <h2 class="t-card">{{ __('bookings.step_schedule') }}</h2>
                    <p class="t-small">{{ __('bookings.step_schedule_hint') }}</p>
                </div>
            </div>
            @if ($addresses->isEmpty())
                <div class="mt-5 rounded-2xl border border-dashed border-line p-6 text-center">
                    <p class="t-card">{{ __('bookings.no_address_title') }}</p>
                    <p class="mt-1.5 t-muted">{{ __('bookings.no_address_hint') }}</p>
                    {{-- Saves the in-progress form to the session, then goes to
                         address management so nothing typed above is lost. --}}
                    <x-button type="submit" formaction="{{ route('customer.booking.draft') }}" formnovalidate
                              name="next" value="addresses" variant="accent" class="mt-4">
                        {{ __('bookings.add_first_address') }}
                    </x-button>
                </div>
            @endif

            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                @if ($addresses->isNotEmpty())
                    <x-field :label="__('bookings.address')" name="address_id" required>
                        <select id="f-address_id" name="address_id" class="input" required>
                            <option value="">{{ __('bookings.select_address') }}</option>
                            @foreach ($addresses as $a)
                                <option value="{{ $a->id }}" @selected(old('address_id', $prefill['address_id'] ?? null) == $a->id)>{{ $a->title }} — {{ $a->street }}, {{ $a->city }}</option>
                            @endforeach
                        </select>
                    </x-field>
                    <div class="flex items-end">
                        <x-button type="submit" formaction="{{ route('customer.booking.draft') }}" formnovalidate
                                  name="next" value="addresses" variant="secondary" class="w-full">{{ __('bookings.manage_addresses') }}</x-button>
                    </div>
                @endif
                <x-field :label="__('bookings.preferred_date')" name="preferred_date" required>
                    <input id="f-preferred_date" type="date" name="preferred_date" min="{{ now()->toDateString() }}" value="{{ old('preferred_date', $prefill['preferred_date'] ?? null) }}" class="input" required>
                </x-field>
                <x-field :label="__('bookings.preferred_time')" name="preferred_time" required>
                    <input id="f-preferred_time" type="time" name="preferred_time" value="{{ old('preferred_time', $prefill['preferred_time'] ?? null) }}" class="input" required>
                </x-field>
            </div>

            @isset($coverage)
                <x-alert :tone="$coverage['covered'] ? 'info' : 'warning'" class="mt-5">
                    {{ $coverage['covered']
                        ? __('bookings.coverage_ok', ['count' => $coverage['count']])
                        : __('bookings.coverage_none') }}
                </x-alert>
            @endisset
        </x-card>

        <div class="flex justify-end">
            <x-button variant="accent" size="lg">
                {{ __('bookings.submit') }}<x-icon name="arrow-right" class="h-4 w-4" />
            </x-button>
        </div>
    </form>
</div>
@endsection
