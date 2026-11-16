<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitWhishPaymentRequest;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\ServiceRequest;
use App\Payments\Money;
use App\Payments\PaymentCaptureService;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    /**
     * Instant (test/card) capture. Unchanged behaviour: every persisted value is
     * server-derived and, once captured, a historical fact. The capture itself
     * now runs through PaymentCaptureService so WHISH approval settles the exact
     * same way.
     */
    public function pay(ServiceRequest $serviceRequest, PaymentCaptureService $capture)
    {
        $this->authorize('pay', $serviceRequest);

        if ($error = $this->notPayable($serviceRequest)) {
            return back()->withErrors(['payment' => $error]);
        }

        $capture->capture($serviceRequest, [
            'transaction_reference' => 'TEST-'.strtoupper(Str::random(12)),
            'payment_method' => 'card',
        ]);

        return redirect()->route('customer.requests.receipt', $serviceRequest)
            ->with('success', __('messages.flash.payment_completed'));
    }

    /**
     * WHISH manual-payment instructions: SALE7LI's WHISH account details plus the
     * exact amount owed. Read-only; the amount is the locked figure, never input.
     */
    public function whish(ServiceRequest $serviceRequest)
    {
        $this->authorize('pay', $serviceRequest);

        if ($error = $this->notPayable($serviceRequest)) {
            return redirect()->route('customer.requests.show', $serviceRequest)
                ->withErrors(['payment' => $error]);
        }

        $existing = $serviceRequest->payment;
        if ($existing && $existing->status === 'pending') {
            return redirect()->route('customer.requests.show', $serviceRequest);
        }

        return view('customer.payments.whish', [
            'serviceRequest' => $serviceRequest,
            'amount' => $serviceRequest->lockedAmount(),
            'whish' => config('sale7li.whish'),
        ]);
    }

    /**
     * Record a WHISH submission as a PENDING payment. The customer supplies only
     * the WHISH reference and an optional proof image; the amount, worker and
     * every money column are server-derived. Nothing is marked paid here — an
     * admin must approve it.
     */
    public function submitWhish(SubmitWhishPaymentRequest $request, ServiceRequest $serviceRequest, PaymentCaptureService $capture)
    {
        if ($error = $this->notPayable($serviceRequest)) {
            return redirect()->route('customer.requests.show', $serviceRequest)
                ->withErrors(['payment' => $error]);
        }

        $existing = $serviceRequest->payment;
        if ($existing && in_array($existing->status, ['pending', 'paid'], true)) {
            return redirect()->route('customer.requests.show', $serviceRequest)
                ->withErrors(['payment' => __('messages.flash.payment_already_paid')]);
        }

        $proofPath = $request->file('proof')?->store('payment-proofs', 'local');

        Payment::updateOrCreate(
            ['service_request_id' => $serviceRequest->id],
            [
                'customer_id' => $serviceRequest->customer_id,
                'worker_id' => $serviceRequest->worker_id,
                'transaction_reference' => 'WHISH-'.strtoupper(Str::random(10)),
                'payment_method' => 'whish',
                'whish_reference' => $request->validated()['whish_reference'],
                'payment_proof_path' => $proofPath,
                'amount' => $serviceRequest->lockedAmount(),
                'currency' => 'USD',
                'status' => 'pending',
                'submitted_at' => now(),
                // A retry after a rejection clears the previous verdict.
                'verified_at' => null,
                'verified_by' => null,
                'rejection_reason' => null,
                'paid_at' => null,
            ],
        );

        Notification::record($serviceRequest->customer_id, 'payment_submitted', $serviceRequest->id, [
            'reference' => $serviceRequest->reference,
        ]);

        return redirect()->route('customer.requests.show', $serviceRequest)
            ->with('success', __('messages.flash.whish_submitted'));
    }

    /**
     * Read-only payment receipt. Every figure is read straight from the frozen
     * Payment row — nothing is recomputed.
     */
    public function receipt(ServiceRequest $serviceRequest)
    {
        $this->authorize('view', $serviceRequest);

        $serviceRequest->load(['payment', 'category', 'invoice']);
        $payment = $serviceRequest->payment;

        abort_if($payment === null || $payment->status !== 'paid', 404);

        return view('customer.payments.receipt', compact('serviceRequest', 'payment'));
    }

    /**
     * The readiness gate shared by the instant and WHISH entry points. Returns a
     * localized error string, or null when the request can be paid.
     */
    private function notPayable(ServiceRequest $serviceRequest): ?string
    {
        if (! in_array($serviceRequest->status, ['accepted', 'scheduled', 'on_the_way', 'in_progress', 'completed'], true)) {
            return __('messages.flash.payment_not_ready');
        }

        if ($serviceRequest->payment_status === 'paid') {
            return __('messages.flash.payment_already_paid');
        }

        $acceptedQuote = $serviceRequest->quotations()->where('status', 'accepted')->exists();

        if (! $acceptedQuote && $serviceRequest->final_total === null) {
            return __('messages.flash.payment_needs_quote');
        }

        $lockedAmount = $serviceRequest->lockedAmount();

        if ($lockedAmount === null || Money::toCents($lockedAmount) <= 0) {
            return __('messages.flash.payment_no_amount');
        }

        if ($serviceRequest->worker === null) {
            return __('messages.flash.payment_no_worker');
        }

        return null;
    }
}
