@extends('layouts.app')
@section('content')
@php($title = __('payments.whish_queue_title'))

<div class="container-app py-8 sm:py-10">
    <x-page-header
        :back="route('admin.payments.index')"
        :backLabel="__('payments.admin_payments_title')"
        :eyebrow="__('admin.eyebrow')"
        :title="__('payments.whish_queue_title')"
        :subtitle="__('payments.whish_queue_sub')" />

    <div class="mt-8 space-y-4">
        @forelse ($payments as $payment)
            <x-card>
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0">
                        <div class="font-black">{{ $payment->request?->reference }}</div>
                        <div class="t-small">{{ $payment->request?->category?->localized_name }}</div>
                    </div>
                    <div class="text-end">
                        <div class="font-black tabular-nums"><x-money :amount="$payment->amount" /></div>
                        <div class="t-small">{{ __('payments.whish_col_submitted') }}: <x-date :value="$payment->submitted_at" :time="true" /></div>
                    </div>
                </div>

                <dl class="mt-4 grid gap-3 border-t border-line pt-4 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="t-muted">{{ __('payments.col_customer') }}</dt>
                        <dd class="font-bold">{{ $payment->customer?->name }}</dd>
                        <dd class="t-small">{{ $payment->customer?->email }}</dd>
                    </div>
                    <div>
                        <dt class="t-muted">{{ __('payments.col_worker') }}</dt>
                        <dd class="font-bold">{{ $payment->worker?->name ?? __('messages.none') }}</dd>
                    </div>
                    <div>
                        <dt class="t-muted">{{ __('payments.whish_col_reference') }}</dt>
                        <dd class="font-mono">{{ $payment->whish_reference }}</dd>
                    </div>
                    <div>
                        <dt class="t-muted">{{ __('payments.whish_proof_label') }}</dt>
                        <dd>
                            @if ($payment->payment_proof_path)
                                <a href="{{ route('attachments.paymentProof', $payment) }}" target="_blank" rel="noopener"
                                   class="font-bold text-ink hover:underline">{{ __('payments.whish_view_proof') }}</a>
                            @else
                                <span class="t-small">{{ __('payments.whish_no_proof') }}</span>
                            @endif
                        </dd>
                    </div>
                </dl>

                <div class="mt-5 flex flex-col gap-3 border-t border-line pt-4 sm:flex-row sm:items-end">
                    <form method="POST" action="{{ route('admin.payments.whish.approve', $payment) }}"
                          data-confirm="{{ __('payments.whish_approve_confirm') }}">
                        @csrf
                        <x-button variant="accent" class="w-full sm:w-auto">{{ __('payments.whish_approve') }}</x-button>
                    </form>

                    <form method="POST" action="{{ route('admin.payments.whish.reject', $payment) }}"
                          data-confirm="{{ __('payments.whish_reject_confirm') }}" class="flex-1">
                        @csrf
                        <label for="reason-{{ $payment->id }}" class="field-label">{{ __('payments.whish_reject_reason_label') }}</label>
                        <div class="flex gap-2">
                            <input id="reason-{{ $payment->id }}" name="reason" class="input" maxlength="500">
                            <x-button variant="danger" class="shrink-0">{{ __('payments.whish_reject') }}</x-button>
                        </div>
                    </form>
                </div>
            </x-card>
        @empty
            <x-card padding="p-0">
                <x-empty icon="wallet" :title="__('payments.whish_queue_empty')" />
            </x-card>
        @endforelse
    </div>

    <div class="mt-5">{{ $payments->links() }}</div>
</div>
@endsection
