@extends('layouts.app')
@section('content')
@php($title = __('payments.admin_payments_title'))

<div class="container-app py-8 sm:py-10">
    <x-page-header :eyebrow="__('admin.eyebrow')" :title="__('payments.admin_payments_title')" :subtitle="__('payments.admin_payments_sub')">
        <x-slot:actions>
            <x-button :href="route('admin.payments.whish')" variant="secondary" size="sm">
                {{ __('payments.whish_queue_link') }}@if ($whishPending) <span class="ms-1 rounded-full bg-brand px-1.5 text-[10px] font-black text-ink">{{ $whishPending }}</span>@endif
            </x-button>
            <x-button :href="route('admin.payments.export', request()->query())" variant="secondary" size="sm">
                {{ __('messages.export_csv') }}
            </x-button>
            <x-button :href="route('admin.payments.reconciliation')" variant="secondary" size="sm">
                {{ __('payments.recon_title') }}
            </x-button>
            <x-button :href="route('admin.settings.commission.edit')" variant="secondary" size="sm">
                {{ __('payments.commission_settings_title') }}
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <x-stat :label="__('payments.summary_gross_volume')" :value="\App\Payments\Money::format($summary->gross)" />
        <x-stat :label="__('payments.platform_commission')" :value="\App\Payments\Money::format($summary->commission)" />
        <x-stat :label="__('payments.worker_net')" :value="\App\Payments\Money::format($summary->net)" />
        <x-stat :label="__('payments.summary_paid_count')" :value="(int) $summary->paid_count" />
        <x-stat :label="__('payments.summary_avg')" :value="\App\Payments\Money::format($summary->average)" />
    </div>

    <form class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_160px_160px_auto]" data-no-busy>
        <label for="f-worker" class="sr-only">{{ __('payments.filter_worker') }}</label>
        <input id="f-worker" name="worker" value="{{ $filters['worker'] }}" class="input" placeholder="{{ __('payments.filter_worker') }}">
        <label for="f-customer" class="sr-only">{{ __('payments.filter_customer') }}</label>
        <input id="f-customer" name="customer" value="{{ $filters['customer'] }}" class="input" placeholder="{{ __('payments.filter_customer') }}">
        <label for="f-status" class="sr-only">{{ __('payments.filter_status') }}</label>
        <select id="f-status" name="status" class="input">
            <option value="">{{ __('payments.all_statuses') }}</option>
            @foreach ($statuses as $s)
                <option value="{{ $s }}" @selected($filters['status'] === $s)>{{ __('status.payment.'.$s) }}</option>
            @endforeach
        </select>
        <div class="grid grid-cols-2 gap-2 sm:col-span-2 lg:col-span-2 lg:grid-cols-2">
            <label for="f-from" class="sr-only">{{ __('payments.filter_from') }}</label>
            <input id="f-from" type="date" name="from" value="{{ $filters['from'] }}" class="input" aria-label="{{ __('payments.filter_from') }}">
            <label for="f-to" class="sr-only">{{ __('payments.filter_to') }}</label>
            <input id="f-to" type="date" name="to" value="{{ $filters['to'] }}" class="input" aria-label="{{ __('payments.filter_to') }}">
        </div>
        <x-button type="submit">{{ __('messages.filter') }}</x-button>
    </form>

    <x-card padding="p-0" class="mt-6 overflow-hidden">
        <div class="table-wrap hidden sm:block">
            <table>
                <thead class="bg-paper text-xs uppercase tracking-wide text-muted">
                    <tr>
                        <th class="p-4 font-semibold">{{ __('payments.col_service') }}</th>
                        <th class="p-4 font-semibold">{{ __('payments.col_customer') }}</th>
                        <th class="p-4 font-semibold">{{ __('payments.col_worker') }}</th>
                        <th class="p-4 font-semibold text-end">{{ __('payments.col_gross') }}</th>
                        <th class="p-4 font-semibold text-end">{{ __('payments.col_rate') }}</th>
                        <th class="p-4 font-semibold text-end">{{ __('payments.col_commission') }}</th>
                        <th class="p-4 font-semibold text-end">{{ __('payments.col_net') }}</th>
                        <th class="p-4 font-semibold text-end">{{ __('payments.col_refunded') }}</th>
                        <th class="p-4 font-semibold">{{ __('payments.col_paid_date') }}</th>
                        <th class="p-4 font-semibold">{{ __('payments.col_status') }}</th>
                        <th class="p-4 font-semibold"><span class="sr-only">{{ __('payments.refund_action') }}</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($payments as $payment)
                        @php($refund = $refundMap[$payment->id] ?? ['refunded' => null, 'is_refunded' => false])
                        <tr>
                            <td class="p-4">
                                <div class="font-bold">{{ $payment->request?->category?->localized_name ?? __('payments.not_available') }}</div>
                                <div class="t-small">{{ $payment->request?->reference }}</div>
                            </td>
                            <td class="p-4">{{ $payment->customer?->name ?? __('reviews.anonymous') }}</td>
                            <td class="p-4">{{ $payment->worker?->name ?? __('payments.not_available') }}</td>
                            <td class="p-4 text-end tabular-nums"><x-money :amount="$payment->amount" /></td>
                            <td class="p-4 text-end tabular-nums">{{ \App\Payments\Money::ratePercent($payment->commission_rate) ?? __('payments.not_available') }}</td>
                            <td class="p-4 text-end tabular-nums"><x-money :amount="$payment->commission_amount" /></td>
                            <td class="p-4 text-end tabular-nums"><x-money :amount="$payment->worker_net" /></td>
                            <td class="p-4 text-end tabular-nums">
                                @if ($refund['refunded'] !== null)
                                    {{ $refund['refunded'] }}
                                @else
                                    <span class="text-faint">{{ __('payments.not_available') }}</span>
                                @endif
                            </td>
                            <td class="p-4 t-small"><x-date :value="$payment->paid_at" /></td>
                            <td class="p-4">
                                <div class="flex items-center gap-1.5">
                                    <x-status type="payment" :value="$payment->status" />
                                    @if ($refund['is_refunded'])
                                        <x-badge tone="neutral">{{ __('payments.refunded') }}</x-badge>
                                    @endif
                                </div>
                            </td>
                            <td class="p-4 text-end">
                                @if ($payment->status === 'paid' && ! $refund['is_refunded'])
                                    <form method="POST" action="{{ route('admin.payments.refund', $payment) }}"
                                          data-confirm="{{ __('payments.refund_confirm', ['amount' => \App\Payments\Money::format($payment->amount), 'reference' => $payment->request?->reference]) }}">
                                        @csrf
                                        <x-button type="submit" variant="danger" size="sm">{{ __('payments.refund_action') }}</x-button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="11"><x-empty icon="wallet" :title="__('payments.no_payments')" :message="__('payments.no_payments_hint')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile: one card per payment (below sm) --}}
        <div class="divide-y divide-line sm:hidden" data-mobile-rows>
            @forelse ($payments as $payment)
                @php($refund = $refundMap[$payment->id] ?? ['refunded' => null, 'is_refunded' => false])
                <x-table-card>
                    <x-slot:heading>
                        <div class="font-bold">{{ $payment->request?->category?->localized_name ?? __('payments.not_available') }}</div>
                        <div class="t-small">{{ $payment->request?->reference }}</div>
                    </x-slot:heading>
                    <x-slot:trailing>
                        <div class="font-black tabular-nums"><x-money :amount="$payment->amount" /></div>
                        <div class="t-small">{{ __('payments.col_gross') }}</div>
                    </x-slot:trailing>
                    <x-slot:rows>
                        <x-table-card-row :label="__('payments.col_customer')">{{ $payment->customer?->name ?? __('reviews.anonymous') }}</x-table-card-row>
                        <x-table-card-row :label="__('payments.col_worker')">{{ $payment->worker?->name ?? __('payments.not_available') }}</x-table-card-row>
                        <x-table-card-row :label="__('payments.col_rate')">{{ \App\Payments\Money::ratePercent($payment->commission_rate) ?? __('payments.not_available') }}</x-table-card-row>
                        <x-table-card-row :label="__('payments.col_commission')"><x-money :amount="$payment->commission_amount" /></x-table-card-row>
                        <x-table-card-row :label="__('payments.col_net')"><x-money :amount="$payment->worker_net" /></x-table-card-row>
                        @if ($refund['refunded'] !== null)
                            <x-table-card-row :label="__('payments.col_refunded')">{{ $refund['refunded'] }}</x-table-card-row>
                        @endif
                        <x-table-card-row :label="__('payments.col_paid_date')"><x-date :value="$payment->paid_at" /></x-table-card-row>
                    </x-slot:rows>
                    <x-slot:actions>
                        <x-status type="payment" :value="$payment->status" />
                        @if ($refund['is_refunded'])
                            <x-badge tone="neutral">{{ __('payments.refunded') }}</x-badge>
                        @endif
                        @if ($payment->status === 'paid' && ! $refund['is_refunded'])
                            <form method="POST" action="{{ route('admin.payments.refund', $payment) }}" class="w-full"
                                  data-confirm="{{ __('payments.refund_confirm', ['amount' => \App\Payments\Money::format($payment->amount), 'reference' => $payment->request?->reference]) }}">
                                @csrf
                                <x-button type="submit" variant="danger" class="w-full">{{ __('payments.refund_action') }}</x-button>
                            </form>
                        @endif
                    </x-slot:actions>
                </x-table-card>
            @empty
                <x-empty icon="wallet" :title="__('payments.no_payments')" :message="__('payments.no_payments_hint')" />
            @endforelse
        </div>
    </x-card>

    <div class="mt-5">{{ $payments->links() }}</div>
</div>
@endsection
