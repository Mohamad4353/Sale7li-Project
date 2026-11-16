@extends('layouts.app')
@section('content')
@php($title = __('payments.whish_title'))
@php($configured = filled($whish['account_number'] ?? null))

<div class="container-app max-w-2xl py-8 sm:py-10">
    <x-page-header
        :back="route('customer.requests.show', $serviceRequest)"
        :backLabel="__('payments.receipt_back_to_request')"
        :eyebrow="$serviceRequest->reference"
        :title="__('payments.whish_title')" />

    @unless ($configured)
        <x-card class="mt-8">
            <x-alert tone="warning">{{ __('payments.whish_not_configured') }}</x-alert>
        </x-card>
    @else
        <x-card class="mt-8">
            <p class="t-muted">{{ __('payments.whish_intro') }}</p>

            <dl class="mt-5 space-y-3 border-t border-line pt-5 text-sm">
                <div class="flex items-center justify-between gap-4">
                    <dt class="t-muted">{{ __('payments.whish_amount_due') }}</dt>
                    <dd class="flex items-center gap-2 font-black tabular-nums">
                        <x-money :amount="$amount" />
                        <x-copy-button :value="$amount" :label="__('payments.copy_amount')" />
                    </dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="t-muted">{{ __('payments.whish_account_name') }}</dt>
                    <dd class="font-bold">{{ $whish['account_name'] ?: '—' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="t-muted">{{ __('payments.whish_account_number') }}</dt>
                    <dd class="flex items-center gap-2 font-mono font-bold">
                        {{ $whish['account_number'] }}
                        <x-copy-button :value="$whish['account_number']" :label="__('payments.copy_account_number')" />
                    </dd>
                </div>
            </dl>

            @if (filled($whish['instructions'] ?? null))
                <p class="mt-4 rounded-xl bg-paper p-3 t-small">{{ $whish['instructions'] }}</p>
            @endif
        </x-card>

        <x-card class="mt-4">
            <h2 class="t-card">{{ __('payments.whish_submit') }}</h2>
            <form method="POST" action="{{ route('customer.payments.whish.submit', $serviceRequest) }}"
                  enctype="multipart/form-data" class="mt-4 space-y-5">
                @csrf

                <x-field :label="__('payments.whish_reference_label')" name="whish_reference"
                         :hint="__('payments.whish_reference_hint')" required>
                    <input id="f-whish_reference" name="whish_reference" class="input"
                           value="{{ old('whish_reference') }}" required maxlength="64" autocomplete="off">
                </x-field>

                <x-field :label="__('payments.whish_proof_label')" name="proof"
                         :hint="__('payments.whish_proof_hint')">
                    <input id="f-proof" type="file" name="proof" class="input"
                           accept="image/jpeg,image/png,image/webp,application/pdf">
                </x-field>

                <x-button class="w-full">{{ __('payments.whish_submit') }}</x-button>
            </form>
        </x-card>
    @endunless
</div>
@endsection
