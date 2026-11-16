@extends('layouts.app')
@section('content')
@php($title = __('payments.commission_settings_title'))

<div class="container-app max-w-xl py-8 sm:py-10">
    <x-page-header
        :back="route('admin.payments.index')"
        :backLabel="__('payments.admin_payments_title')"
        :eyebrow="__('admin.eyebrow')"
        :title="__('payments.commission_settings_title')"
        :subtitle="__('payments.commission_settings_sub')" />

    <x-card class="mt-8">
        <div class="rounded-2xl border border-line bg-paper p-5">
            <div class="t-label">{{ __('payments.current_rate') }}</div>
            <div class="mt-1 text-3xl font-black tracking-[-.03em]">{{ \App\Payments\Money::ratePercent($rate) }}</div>
            <div class="mt-1 t-small">{{ $rate }}</div>
        </div>

        <form method="POST" action="{{ route('admin.settings.commission.update') }}" class="mt-6 grid gap-5">
            @csrf
            @method('PUT')

            <x-field :label="__('payments.new_rate')" name="rate" :hint="__('payments.rate_help')" required>
                <input id="f-rate" name="rate" type="text" inputmode="decimal"
                       value="{{ old('rate', $rate) }}" class="input" required
                       pattern="^\d(\.\d{1,4})?$" placeholder="0.1000">
            </x-field>

            <x-alert tone="info">{{ __('payments.rate_future_note') }}</x-alert>

            <x-button class="w-full">{{ __('payments.save_rate') }}</x-button>
        </form>
    </x-card>
</div>
@endsection
