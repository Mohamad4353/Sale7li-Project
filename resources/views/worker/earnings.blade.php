@extends('layouts.app')
@section('content')
@php($title = __('payments.earnings_title'))
@php($paidJobs = (int) $totals->jobs)

<div class="container-app py-8 sm:py-10">
    <x-page-header :eyebrow="__('dashboard.worker_portal')" :title="__('payments.earnings_title')" :subtitle="__('payments.earnings_sub')" />

    <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <x-stat :label="__('payments.gross')" :value="\App\Payments\Money::format($totals->gross)" />
        <x-stat :label="__('payments.platform_commission')" :value="\App\Payments\Money::format($totals->commission)" />
        <x-stat :label="__('payments.net_at_capture')" :value="\App\Payments\Money::format($totals->net)" />
        <x-stat :label="__('payments.retained')" :value="\App\Payments\Money::format($retained)" :hint="__('payments.retained_hint')" />
        <x-stat :label="__('payments.refunded')" :value="\App\Payments\Money::format($refunded)" />
        <x-stat :label="__('payments.paid_jobs')" :value="$paidJobs" />
    </div>

    <x-card padding="p-0" class="mt-8 overflow-hidden">
        <h2 class="t-section px-6 pt-6">{{ __('payments.recent') }}</h2>

        @if ($payments->isEmpty())
            <x-empty icon="wallet" :title="__('payments.no_paid_jobs')" :message="__('payments.no_paid_jobs_hint')" />
        @else
            {{-- Desktop / tablet: the full table (sm and up) --}}
            <div class="table-wrap hidden sm:block mt-4">
                <table>
                    <thead class="bg-paper text-xs uppercase tracking-wide text-muted">
                        <tr>
                            <th class="p-4 font-semibold">{{ __('payments.col_service') }}</th>
                            <th class="p-4 font-semibold">{{ __('payments.col_customer') }}</th>
                            <th class="p-4 font-semibold text-end">{{ __('payments.col_gross') }}</th>
                            <th class="p-4 font-semibold text-end">{{ __('payments.col_commission') }}</th>
                            <th class="p-4 font-semibold text-end">{{ __('payments.col_net') }}</th>
                            <th class="p-4 font-semibold text-end">{{ __('payments.col_rate') }}</th>
                            <th class="p-4 font-semibold">{{ __('payments.col_paid_date') }}</th>
                            <th class="p-4 font-semibold">{{ __('payments.col_status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($payments as $payment)
                            <tr>
                                <td class="p-4">
                                    <div class="font-bold">{{ $payment->request?->category?->localized_name ?? __('payments.not_available') }}</div>
                                    <div class="t-small">{{ $payment->request?->reference }}</div>
                                </td>
                                <td class="p-4">{{ $payment->customer?->name ?? __('reviews.anonymous') }}</td>
                                <td class="p-4 text-end tabular-nums"><x-money :amount="$payment->amount" /></td>
                                <td class="p-4 text-end tabular-nums"><x-money :amount="$payment->commission_amount" /></td>
                                <td class="p-4 text-end font-bold tabular-nums"><x-money :amount="$payment->worker_net" /></td>
                                <td class="p-4 text-end tabular-nums">
                                    {{ \App\Payments\Money::ratePercent($payment->commission_rate) ?? __('payments.not_available') }}
                                </td>
                                <td class="p-4 t-small"><x-date :value="$payment->paid_at" /></td>
                                <td class="p-4">
                                    <div class="flex items-center gap-1.5">
                                        <x-status type="payment" :value="$payment->status" />
                                        @if ($payment->refunds->firstWhere('status', 'completed'))
                                            <x-badge tone="neutral">{{ __('payments.refunded') }}</x-badge>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile: one card per payment (below sm) --}}
            <div class="mt-4 divide-y divide-line sm:hidden" data-mobile-rows>
                @foreach ($payments as $payment)
                    <x-table-card>
                        <x-slot:heading>
                            <div class="font-bold">{{ $payment->request?->category?->localized_name ?? __('payments.not_available') }}</div>
                            <div class="t-small">{{ $payment->request?->reference }}</div>
                        </x-slot:heading>
                        <x-slot:trailing>
                            <div class="font-black tabular-nums"><x-money :amount="$payment->worker_net" /></div>
                            <div class="t-small">{{ __('payments.col_net') }}</div>
                        </x-slot:trailing>
                        <x-slot:rows>
                            <x-table-card-row :label="__('payments.col_customer')">{{ $payment->customer?->name ?? __('reviews.anonymous') }}</x-table-card-row>
                            <x-table-card-row :label="__('payments.col_gross')"><x-money :amount="$payment->amount" /></x-table-card-row>
                            <x-table-card-row :label="__('payments.col_commission')"><x-money :amount="$payment->commission_amount" /></x-table-card-row>
                            <x-table-card-row :label="__('payments.col_rate')">{{ \App\Payments\Money::ratePercent($payment->commission_rate) ?? __('payments.not_available') }}</x-table-card-row>
                            <x-table-card-row :label="__('payments.col_paid_date')"><x-date :value="$payment->paid_at" /></x-table-card-row>
                        </x-slot:rows>
                        <x-slot:actions>
                            <x-status type="payment" :value="$payment->status" />
                            @if ($payment->refunds->firstWhere('status', 'completed'))
                                <x-badge tone="neutral">{{ __('payments.refunded') }}</x-badge>
                            @endif
                        </x-slot:actions>
                    </x-table-card>
                @endforeach
            </div>

            <div class="p-5">{{ $payments->links() }}</div>
        @endif
    </x-card>
</div>
@endsection
