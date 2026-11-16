<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Quotation;
use App\Payments\Money;
use Illuminate\Support\Facades\DB;

class QuotationController extends Controller
{
    public function accept(Quotation $quotation)
    {
        $quotation->load('request');
        $this->authorize('respond', $quotation);

        if ($quotation->status !== 'pending'
            || $quotation->request->status === 'cancelled'
            || $quotation->request->payment_status === 'paid') {
            return back()->withErrors(['quotation' => __('messages.flash.quotation_closed')]);
        }

        DB::transaction(function () use ($quotation) {
            $request = $quotation->request;

            $request->quotations()
                ->where('id', '!=', $quotation->id)
                ->where('status', 'pending')
                ->update(['status' => 'rejected', 'responded_at' => now()]);

            $quotation->update(['status' => 'accepted', 'responded_at' => now()]);
            $request->update(['quoted_total' => $quotation->total]);
        });

        // Display-only formatting of an already-frozen amount — no financial state changes.
        Notification::record($quotation->worker_id, 'quotation_accepted', $quotation->request->id, [
            'reference' => $quotation->request->reference,
            'amount' => Money::format($quotation->total),
        ]);

        return back()->with('success', __('messages.flash.quotation_accepted'));
    }

    public function reject(Quotation $quotation)
    {
        $quotation->load('request');
        $this->authorize('respond', $quotation);

        if ($quotation->status !== 'pending') {
            return back()->withErrors(['quotation' => __('messages.flash.quotation_closed')]);
        }

        $quotation->update(['status' => 'rejected', 'responded_at' => now()]);

        Notification::record($quotation->worker_id, 'quotation_rejected', $quotation->request->id, [
            'reference' => $quotation->request->reference,
        ]);

        return back()->with('success', __('messages.flash.quotation_rejected'));
    }
}
