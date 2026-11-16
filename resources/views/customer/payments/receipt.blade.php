@extends('layouts.app')
@section('content')
@php($title = __('payments.receipt_title'))

<div class="container-app max-w-2xl py-8 sm:py-10">
    <x-page-header
        :back="route('customer.requests.show', $serviceRequest)"
        :backLabel="__('payments.receipt_back_to_request')"
        :eyebrow="$serviceRequest->reference"
        :title="__('payments.receipt_title')">
        <x-slot:actions>
            <button type="button" x-data @click="window.print()"
                    class="no-print inline-flex items-center gap-2 rounded-xl border border-line bg-surface px-3 py-2 text-[13px] font-bold text-ink transition hover:bg-paper">
                <x-icon name="clipboard" class="h-4 w-4" />{{ __('messages.print') }}
            </button>
        </x-slot:actions>
    </x-page-header>

    <x-card class="print-sheet mt-8 text-center">
        <span class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-emerald-100 text-emerald-700">
            <x-icon name="check" class="h-6 w-6" />
        </span>
        <h2 class="mt-4 t-card">{{ __('payments.receipt_success') }}</h2>
        <p class="mt-1 t-muted">{{ __('payments.receipt_success_hint') }}</p>

        <dl class="mt-6 space-y-3 border-t border-line pt-6 text-start text-sm">
            <div class="flex justify-between gap-4">
                <dt class="t-muted">{{ __('payments.receipt_ref') }}</dt>
                <dd class="font-bold">{{ $serviceRequest->reference }} · {{ $serviceRequest->category->localized_name }}</dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt class="t-muted">{{ __('payments.receipt_amount') }}</dt>
                <dd class="font-black tabular-nums"><x-money :amount="$payment->amount" /></dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt class="t-muted">{{ __('payments.receipt_date') }}</dt>
                <dd class="font-bold"><x-date :value="$payment->paid_at" :time="true" /></dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt class="t-muted">{{ __('payments.method_label') }}</dt>
                <dd class="font-bold">{{ $payment->payment_method === 'whish' ? __('payments.method_whish') : __('payments.method_card') }}</dd>
            </div>
            @if ($payment->payment_method === 'whish' && $payment->whish_reference)
                <div class="flex justify-between gap-4">
                    <dt class="t-muted">{{ __('payments.whish_col_reference') }}</dt>
                    <dd class="font-mono text-xs">{{ $payment->whish_reference }}</dd>
                </div>
            @endif
            <div class="flex justify-between gap-4">
                <dt class="t-muted">{{ __('payments.receipt_txn') }}</dt>
                <dd class="font-mono text-xs">{{ $payment->transaction_reference }}</dd>
            </div>
        </dl>

        <x-button :href="route('customer.requests.invoice', $serviceRequest)" variant="secondary" class="mt-6 w-full no-print">
            {{ __('payments.receipt_view_invoice') }}
        </x-button>
    </x-card>
</div>
@endsection
