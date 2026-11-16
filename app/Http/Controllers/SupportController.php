<?php

namespace App\Http\Controllers;

class SupportController extends Controller
{
    /**
     * General support entry point for any signed-in user: the configurable
     * support address, how per-job problem reports work, and the status of
     * complaints this user has filed.
     */
    public function show()
    {
        $complaints = auth()->user()
            ->complaints()
            ->with('request:id,reference')
            ->latest()
            ->get();

        return view('support.index', [
            'supportEmail' => config('sale7li.support_email'),
            'complaints' => $complaints,
        ]);
    }
}
