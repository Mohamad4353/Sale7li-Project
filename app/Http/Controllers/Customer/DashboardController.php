<?php

namespace App\Http\Controllers\Customer;

use App\Analytics\CustomerAnalytics;
use App\Http\Controllers\Controller;
use App\Models\ServiceCategory;

class DashboardController extends Controller
{
    public function __invoke(CustomerAnalytics $analytics)
    {
        $u = auth()->user();
        $stats = ['total' => $u->customerRequests()->count(), 'pending' => $u->customerRequests()->where('status', 'pending')->count(), 'active' => $u->customerRequests()->whereIn('status', ['accepted', 'scheduled', 'on_the_way', 'in_progress'])->count(), 'completed' => $u->customerRequests()->where('status', 'completed')->count()];
        $requests = $u->customerRequests()->with(['category', 'worker.workerProfile', 'appointment'])->latest()->limit(5)->get();
        $categories = ServiceCategory::where('is_active', true)->limit(4)->get();

        // Read-only, own-data-only analytics for this customer.
        $requestsByStatus = $analytics->requestsByStatus($u);
        $requestsPerDay = $analytics->requestsPerDay($u);

        return view('customer.dashboard', compact('stats', 'requests', 'categories', 'requestsByStatus', 'requestsPerDay'));
    }
}
