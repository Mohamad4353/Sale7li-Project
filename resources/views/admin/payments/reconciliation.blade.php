@extends('layouts.app')
@section('content')
@php($title = __('payments.recon_title'))

<div class="container-app py-8 sm:py-10">
    <x-page-header
        :back="route('admin.payments.index')"
        :backLabel="__('payments.admin_payments_title')"
        :eyebrow="__('admin.eyebrow')"
        :title="__('payments.recon_title')"
        :subtitle="__('payments.recon_sub')">
        <x-slot:actions>
            <x-button :href="route('admin.payments.reconciliation.export')" variant="secondary" size="sm">
                {{ __('messages.export_csv') }}
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat :label="__('payments.recon_checked')" :value="$report->checked" />
        <x-stat :label="__('payments.recon_reconciled')" :value="$report->reconciled" />
        <x-stat :label="__('payments.recon_discrepancies')" :value="$report->discrepancyCount()" />
        <x-stat :label="__('payments.recon_refunded_payments')" :value="$report->refundedPayments" />
    </div>

    <div class="mt-4 grid gap-4 sm:grid-cols-3">
        <x-stat :label="__('payments.recon_gross_captured')" :value="\App\Payments\Money::format($report->grossCaptured)" />
        <x-stat :label="__('payments.recon_total_refunded')" :value="\App\Payments\Money::format($report->totalRefunded)" />
        <x-stat :label="__('payments.recon_retained')" :value="\App\Payments\Money::format($report->retained)" />
    </div>

    <x-card padding="p-0" class="mt-8 overflow-hidden">
        <h2 class="t-section px-6 pt-6">{{ __('payments.recon_findings') }}</h2>

        @if ($report->isClean())
            <x-empty icon="shield" :title="__('payments.recon_clean_title')" :message="__('payments.recon_clean_hint')" />
        @else
            <div class="table-wrap mt-4">
                <table>
                    <thead class="bg-paper text-xs uppercase tracking-wide text-muted">
                        <tr>
                            <th class="p-4 font-semibold">{{ __('payments.recon_col_issue') }}</th>
                            <th class="p-4 font-semibold">{{ __('payments.col_service') }}</th>
                            <th class="p-4 font-semibold">{{ __('payments.col_customer') }}</th>
                            <th class="p-4 font-semibold">{{ __('payments.col_worker') }}</th>
                            <th class="p-4 font-semibold text-end">{{ __('payments.recon_col_expected') }}</th>
                            <th class="p-4 font-semibold text-end">{{ __('payments.recon_col_actual') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($report->discrepancies as $d)
                            <tr>
                                <td class="p-4">
                                    <div class="font-bold">{{ $d->label() }}</div>
                                    @if ($d->paymentId)<div class="t-small">{{ __('payments.recon_payment_ref', ['id' => $d->paymentId]) }}</div>@endif
                                </td>
                                <td class="p-4 t-small">{{ $d->reference ?? __('messages.none') }}</td>
                                <td class="p-4">{{ $d->customer ?? __('messages.none') }}</td>
                                <td class="p-4">{{ $d->worker ?? __('messages.none') }}</td>
                                <td class="p-4 text-end tabular-nums">{{ $d->expected ?? __('messages.none') }}</td>
                                <td class="p-4 text-end tabular-nums">{{ $d->actual ?? __('messages.none') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>
</div>
@endsection
