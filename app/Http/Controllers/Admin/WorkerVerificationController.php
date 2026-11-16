<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WorkerProfile;
use Illuminate\Http\Request;

class WorkerVerificationController extends Controller
{
    public function update(Request $request, WorkerProfile $workerProfile)
    {
        $data = $request->validate(['verification_status' => 'required|in:verified,rejected,suspended,pending']);
        $workerProfile->update($data);

        return back()->with('success', __('messages.flash.verification_updated'));
    }
}
