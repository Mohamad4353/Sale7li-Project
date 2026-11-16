<?php

namespace App\Http\Controllers\Worker;

use App\Analytics\WorkerAnalytics;
use App\Http\Controllers\Controller;
use App\Models\Payment;

class DashboardController extends Controller
{
    public function __invoke(WorkerAnalytics $analytics)
    {
        $u = auth()->user();
        // Earnings = the worker's net at capture, from frozen Payment snapshots
        // (the same source of truth as the /worker/earnings page). Never the
        // gross `final_total` of completed jobs, which ignores commission and
        // includes completed jobs the customer has not paid for.
        $stats = [
            'today' => $u->workerRequests()->whereDate('preferred_date', today())->count(),
            'pending' => $u->workerRequests()->where('status', 'pending')->count(),
            'completed' => $u->workerRequests()->where('status', 'completed')->count(),
            'earnings' => Payment::query()->where('worker_id', $u->id)->where('status', 'paid')->sum('worker_net'),
        ];
        $requests = $u->workerRequests()->with(['category', 'customer', 'address'])->latest()->limit(8)->get();

        $profile = $u->workerProfile;
        $profile->setRelation('user', $u);
        $checklist = $profile->completenessItems();
        $completeness = $profile->completeness();
        $canGoLive = $profile->canGoLive();

        // Read-only, own-data-only analytics for this professional.
        $jobsByStatus = $analytics->jobsByStatus($u);
        $completedJobsPerDay = $analytics->completedJobsPerDay($u);

        return view('worker.dashboard', compact(
            'stats', 'requests', 'profile', 'checklist', 'completeness', 'canGoLive',
            'jobsByStatus', 'completedJobsPerDay',
        ));
    }
}
