<?php

namespace App\Payments;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Quotation;
use App\Models\ServiceRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The single capture path for a service-request payment. Both the instant
 * (card/test) flow and WHISH admin-approval settle through here, so commission,
 * worker-net and the invoice are computed exactly once, the same way:
 *
 *   amount            = ServiceRequest::lockedAmount()  (final_total ?? quoted_total)
 *   commission_rate   = the platform rate active now (frozen onto the row)
 *   commission_amount = CommissionService result (integer-cent, round half up)
 *   worker_net        = amount - commission_amount
 *
 * The invariant quotation.total == invoice.total == payment.amount ==
 * final_total is preserved because every figure derives from lockedAmount and
 * the accepted quotation — never from request input.
 */
class PaymentCaptureService
{
    public function __construct(private CommissionService $commission) {}

    /**
     * Settle a payment for a request as `paid`, write/refresh its invoice and
     * mark the request paid — all in one transaction.
     *
     * @param  array<string, mixed>  $identity  caller-owned, non-financial columns
     *                                          for the Payment row (e.g. transaction_reference, payment_method, and on
     *                                          WHISH approval verified_at / verified_by). Money columns are ignored here
     *                                          even if passed.
     */
    public function capture(ServiceRequest $request, array $identity = []): Payment
    {
        $worker = $request->worker;
        $lockedAmount = $request->lockedAmount();
        $acceptedQuote = $request->quotations()->where('status', 'accepted')->first();

        $result = $this->commission->calculate($lockedAmount);

        return DB::transaction(function () use ($request, $worker, $result, $acceptedQuote, $identity) {
            $financial = [
                'customer_id' => $request->customer_id,
                'worker_id' => $worker->id,
                'amount' => $result->grossAmount(),
                'commission_rate' => $result->rate,
                'commission_amount' => $result->commissionAmount(),
                'worker_net' => $result->workerNetAmount(),
                'currency' => 'USD',
                'status' => 'paid',
                'paid_at' => now(),
            ];

            // Money columns are always server-derived; a caller cannot override them.
            $attributes = [...$identity, ...$financial];

            $payment = Payment::updateOrCreate(
                ['service_request_id' => $request->id],
                $attributes,
            );

            $request->update(['payment_status' => 'paid']);

            Invoice::updateOrCreate(
                ['service_request_id' => $request->id],
                [
                    'invoice_number' => 'INV-'.strtoupper(Str::random(8)),
                    'customer_id' => $request->customer_id,
                    'worker_id' => $worker->id,
                    ...$this->invoiceBreakdown($request, $acceptedQuote, $result->grossAmount()),
                    'payment_status' => 'paid',
                ],
            );

            return $payment->fresh();
        });
    }

    /**
     * Invoice figures, taken straight from the accepted quotation so that
     * quotation.total == invoice.total == payment.amount == final_total.
     * With no accepted quotation the locked amount stands alone.
     *
     * Each line item carries a stable `key` so the invoice view can render a
     * localized label at display time; `label` is kept as an English snapshot
     * for backward compatibility and for any non-Blade reader.
     *
     * @return array{line_items: array<int, array{key: string, label: string, amount: string}>, subtotal: string, discount: string, tax: string, total: string}
     */
    public function invoiceBreakdown(ServiceRequest $request, ?Quotation $quote, string $total): array
    {
        if (! $quote) {
            return [
                'line_items' => [['key' => 'service', 'label' => $request->category->name, 'amount' => $total]],
                'subtotal' => $total,
                'discount' => '0.00',
                'tax' => '0.00',
                'total' => $total,
            ];
        }

        $lineItems = [['key' => 'service', 'label' => $request->category->name, 'amount' => (string) $quote->base_price]];

        if (Money::toCents($quote->additional_charges) > 0) {
            $lineItems[] = ['key' => 'additional_charges', 'label' => 'Additional charges', 'amount' => (string) $quote->additional_charges];
        }
        if (Money::toCents($quote->discount) > 0) {
            $lineItems[] = ['key' => 'discount', 'label' => 'Discount', 'amount' => '-'.$quote->discount];
        }
        if (Money::toCents($quote->tax) > 0) {
            $lineItems[] = ['key' => 'tax', 'label' => 'Tax', 'amount' => (string) $quote->tax];
        }

        $subtotalCents = Money::toCents($quote->base_price) + Money::toCents($quote->additional_charges);

        return [
            'line_items' => $lineItems,
            'subtotal' => Money::toDecimalString($subtotalCents),
            'discount' => (string) $quote->discount,
            'tax' => (string) $quote->tax,
            'total' => $total,
        ];
    }
}
