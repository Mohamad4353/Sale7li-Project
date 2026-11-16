<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateWorkerProfileRequest;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('worker.profile', ['profile' => auth()->user()->workerProfile]);
    }

    public function update(UpdateWorkerProfileRequest $request)
    {
        $data = $request->validated();
        $user = auth()->user();
        $profile = $user->workerProfile;

        $user->update([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
        ]);

        $attributes = [
            'bio' => $data['bio'] ?? null,
            'bio_ar' => $data['bio_ar'] ?? null,
            'experience_years' => $data['experience_years'],
            'service_radius_km' => $data['service_radius_km'],
        ];

        if ($request->hasFile('photo')) {
            // Laravel generates a random hashed filename with the real
            // extension; the original client name never touches the path. The
            // `local` disk is private (storage/app/private), so there is no
            // predictable public URL — the photo is only reachable through the
            // authorized `workers.photo` route.
            $path = $request->file('photo')->store('worker-photos', 'local');

            if ($profile->photo) {
                Storage::disk('local')->delete($profile->photo);
            }

            $attributes['photo'] = $path;
        }

        $profile->update($attributes);

        return back()->with('success', __('messages.flash.profile_updated'));
    }
}
