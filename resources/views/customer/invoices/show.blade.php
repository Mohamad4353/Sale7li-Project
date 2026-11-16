@extends('layouts.app')
@section('content')
@php($title = __('payments.invoice').' · '.$invoice->invoice_number)

<div class="container-app max-w-3xl py-8 sm:py-10">
    <x-page-header
        :back="route('customer.requests.show', $serviceRequest)"
        :backLabel="__('requests.back_to_requests')"
        :eyebrow="$serviceRequest->reference"
        :title="__('payments.invoice')">
        <x-slot:actions>
            @if ($refund)
                <x-badge tone="neutral" dot>{{ __('status.payment.refunded') }}</x-badge>
            @else
                <x-status type="payment" :value="$invoice->payment_status" />
            @endif
            <button type="button" x-data @click="window.print()"
                    class="no-print inline-flex items-center gap-2 rounded-xl border border-line bg-surface px-3 py-2 text-[13px] font-bold text-ink transition hover:bg-paper">
                <x-icon name="clipboard" class="h-4 w-4" />{{ __('messages.print') }}
            </button>
        </x-slot:actions>
    </x-page-header>

    <x-card class="print-sheet mt-8">
        <div class="flex flex-wrap items-start justify-between gap-4 border-b border-line pb-5">
            <div>
                <div class="t-label">{{ __('payments.invoice_number') }}</div>
                <div class="mt-1 text-lg font-black">{{ $invoice->invoice_number }}</div>
            </div>
            <div class="text-end">
                <div class="t-label">{{ __('payments.invoice_date') }}</div>
                <div class="mt-1 font-bold"><x-date :value="$invoice->created_at" /></div>
            </div>
        </div>

        <div class="grid gap-5 py-5 sm:grid-cols-2">
            <div>
                <div class="t-label">{{ __('payments.col_service') }}</div>
                <div class="mt-1 font-bold">{{ $serviceRequest->category->localized_name }}</div>
            </div>
            <div>
                <div class="t-label">{{ __('payments.col_customer') }}</div>
                <div class="mt-1 font-bold">{{ $serviceRequest->customer->name }}</div>
            </div>
            @if ($serviceRequest->worker)
                <div>
                    <div class="t-label">{{ __('payments.col_worker') }}</div>
                    <div class="mt-1 font-bold">{{ $serviceRequest->worker->name }}</div>
                </div>
            @endif
        </div>

        <div class="table-wrap border-t border-line pt-5">
            <table>
                <thead class="text-xs uppercase tracking-wide text-muted">
                    <tr>
                        <th class="py-2 pe-4 font-semibold">{{ __('payments.line_item') }}</th>
                        <th class="py-2 ps-4 font-semibold text-end">{{ __('payments.col_gross') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($invoice->line_items as $item)
                        @php($label = match ($item['key'] ?? null) {
                            'service' => $serviceRequest->category?->localized_name ?? ($item['label'] ?? '—'),
                            'additional_charges' => __('payments.line_additional_charges'),
                            'discount' => __('payments.discount'),
                            'tax' => __('payments.tax'),
                            default => $item['label'] ?? '—',
                        })
                        <tr>
                            <td class="py-3 pe-4">{{ $label }}</td>
                            <td class="py-3 ps-4 text-end tabular-nums"><x-money :amount="$item['amount'] ?? null" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <dl class="mt-5 space-y-2 border-t border-line pt-5 text-sm">
            <div class="flex justify-between">
                <dt class="t-muted">{{ __('payments.subtotal') }}</dt>
                <dd class="tabular-nums"><x-money :amount="$invoice->subtotal" /></dd>
            </div>
            <div class="flex justify-between">
                <dt class="t-muted">{{ __('payments.discount') }}</dt>
                <dd class="tabular-nums">−<x-money :amount="$invoice->discount" /></dd>
            </div>
            <div class="flex justify-between">
                <dt class="t-muted">{{ __('payments.tax') }}</dt>
                <dd class="tabular-nums"><x-money :amount="$invoice->tax" /></dd>
            </div>
            <div class="flex justify-between border-t border-line pt-2 text-base font-black">
                <dt>{{ __('payments.total') }}</dt>
                <dd class="tabular-nums"><x-money :amount="$invoice->total" /></dd>
            </div>
        </dl>

        @if ($refund)
            <div class="mt-5 border-t border-line pt-5">
                <x-alert tone="info" :title="__('payments.refunded_notice_title')">
                    {{ __('payments.refunded_notice_body', [
                        'amount' => \App\Payments\Money::format($refund->amount),
                        'date' => $refund->processed_at?->translatedFormat('M j, Y'),
                    ]) }}
                </x-alert>
            </div>
        @endif
    </x-card>
</div>
@endsection
