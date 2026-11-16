<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCommissionRateRequest;
use App\Models\ActivityLog;
use App\Models\PlatformSetting;
use App\Payments\CommissionService;

/**
 * The only Step 3 financial write: the active platform commission rate, stored
 * in platform_settings (the single source of truth). A change applies to future
 * payment captures only — historical Payment snapshots are never touched.
 */
class CommissionSettingController extends Controller
{
    public function edit(CommissionService $commission)
    {
        return view('admin.settings.commission', ['rate' => $commission->activeRate()]);
    }

    public function update(UpdateCommissionRateRequest $request, CommissionService $commission)
    {
        $old = $commission->activeRate();
        $new = $commission->normalizeRate($request->validated()['rate']);

        PlatformSetting::setValue(PlatformSetting::COMMISSION_RATE, $new);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'commission_rate.updated',
            'metadata' => ['from' => $old, 'to' => $new],
            'ip_address' => $request->ip(),
        ]);

        return redirect()
            ->route('admin.settings.commission.edit')
            ->with('success', __('messages.flash.commission_rate_updated', ['rate' => $new]));
    }
}
