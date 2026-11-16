<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;

/**
 * Read-only customer invoice. The persisted Invoice record is the source of
 * truth — totals and line items are shown exactly as frozen at payment
 * capture, never recomputed from the quotation or the live commission rate.
 */
class InvoiceController extends Controller
{
    public function show(ServiceRequest $serviceRequest)
    {
        // ServiceRequestPolicy::view — the owning customer (or assigned worker);
        // the route middleware already limits this to the customer role.
        $this->authorize('view', $serviceRequest);

        $serviceRequest->load(['invoice', 'category', 'worker:id,name', 'customer:id,name', 'payment.refunds']);

        $invoice = $serviceRequest->invoice;
        abort_if($invoice === null, 404);

        // Derived, never a mutation of the invoice: the completed refund (if any)
        // for this request's payment. Original invoice totals stay untouched.
        $refund = $serviceRequest->payment?->refunds->firstWhere('status', 'completed');

        return view('customer.invoices.show', compact('serviceRequest', 'invoice', 'refund'));
    }
}
