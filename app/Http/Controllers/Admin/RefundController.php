<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RefundPaymentRequest;
use App\Models\Payment;
use App\Payments\Money;
use App\Payments\RefundException;
use App\Payments\RefundService;
use Illuminate\Database\QueryException;

/**
 * Admin-initiated full refund of a captured payment. This is the only Step 4
 * financial write. The service creates a Refund ledger row and its audit entry
 * in one transaction — it never mutates the payment or its invoice.
 */
class RefundController extends Controller
{
    public function store(RefundPaymentRequest $request, Payment $payment, RefundService $refunds)
    {
        try {
            $refund = $refunds->fullyRefund($payment, $request->user(), $request->validated()['reason'] ?? null);
        } catch (RefundException $e) {
            return back()->withErrors(['refund' => __($e->reasonKey)]);
        } catch (QueryException) {
            // UNIQUE(payment_id, type) backstop, or another write failure — the
            // whole transaction rolled back, so nothing partial was persisted.
            return back()->withErrors(['refund' => __('payments.refund_error_failed')]);
        }

        return back()->with('success', __('messages.flash.refund_completed', [
            'amount' => Money::format($refund->amount),
        ]));
    }
}
