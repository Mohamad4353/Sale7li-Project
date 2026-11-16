<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAvailabilityRequest;
use App\Models\WorkerAvailability;
use Illuminate\Support\Facades\DB;

/**
 * A professional's weekly availability. One row per weekday
 * (0 = Monday … 6 = Sunday), matching the existing unique(worker_id,
 * day_of_week) constraint — that constraint is intentionally kept.
 *
 * Times are stored and displayed in the application's local time; the DB `time`
 * columns carry no timezone and the platform runs in a single timezone.
 */
class AvailabilityController extends Controller
{
    public function edit()
    {
        $availability = auth()->user()->workerProfile->availability()->get();

        return view('worker.availability', compact('availability'));
    }

    public function update(UpdateAvailabilityRequest $request)
    {
        $workerId = auth()->id();
        $days = $request->validated()['days'];

        DB::transaction(function () use ($days, $workerId) {
            foreach (range(0, 6) as $index) {
                $day = $days[$index] ?? $days[(string) $index] ?? null;
                $enabled = $day && filter_var($day['is_available'] ?? false, FILTER_VALIDATE_BOOLEAN);

                WorkerAvailability::updateOrCreate(
                    ['worker_id' => $workerId, 'day_of_week' => $index],
                    [
                        'is_available' => $enabled,
                        'start_time' => $enabled ? ($day['start'] ?? null) : null,
                        'end_time' => $enabled ? ($day['end'] ?? null) : null,
                    ],
                );
            }
        });

        return back()->with('success', __('messages.flash.availability_updated'));
    }
}
