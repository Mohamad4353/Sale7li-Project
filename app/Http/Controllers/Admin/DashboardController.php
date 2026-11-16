<?php

namespace App\Http\Controllers\Admin;

use App\Analytics\AdminAnalytics;
use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\WorkerProfile;

class DashboardController extends Controller
{
    public function __invoke(AdminAnalytics $analytics)
    {
        // Every figure is a real aggregate over the live tables — no estimates.
        $stats = [
            'users' => User::count(),
            'customers' => User::where('role', 'customer')->count(),
            'workers' => User::where('role', 'worker')->count(),
            'requests' => ServiceRequest::count(),
            'active_requests' => ServiceRequest::whereIn('status', ['pending', 'accepted', 'scheduled', 'on_the_way', 'in_progress'])->count(),
            'completed_requests' => ServiceRequest::where('status', 'completed')->count(),
            'cancelled_requests' => ServiceRequest::whereIn('status', ['cancelled', 'rejected'])->count(),
            'payments_completed' => Payment::where('status', 'paid')->count(),
            'payments_pending' => Payment::where('status', 'pending')->count(),
            'whish_pending' => Payment::where('payment_method', 'whish')->where('status', 'pending')->count(),
            'refunds' => Refund::where('status', Refund::STATUS_COMPLETED)->count(),
            'complaints_open' => Complaint::whereIn('status', ['open', 'investigating'])->count(),
            'revenue' => Payment::where('status', 'paid')->sum('amount'),
        ];

        $pendingWorkers = WorkerProfile::with('user')->where('verification_status', 'pending')->latest()->limit(6)->get();
        $requests = ServiceRequest::with(['customer', 'worker', 'category'])->latest()->limit(8)->get();

        // Read-only operational analytics — complements the $stats above, never
        // replaces them. See App\Analytics\AdminAnalytics.
        $kpis = $analytics->kpis();
        $requestsPerDay = $analytics->requestsPerDay();
        $revenuePerDay = $analytics->revenuePerDay();
        $requestsByCategory = $analytics->requestsByCategory();
        $requestsByStatus = $analytics->requestsByStatus();
        $newCustomersPerDay = $analytics->newCustomersPerDay();
        $workerVerification = $analytics->workerVerificationBreakdown();

        return view('admin.dashboard', compact(
            'stats', 'pendingWorkers', 'requests',
            'kpis', 'requestsPerDay', 'revenuePerDay', 'requestsByCategory',
            'requestsByStatus', 'newCustomersPerDay', 'workerVerification',
        ));
    }
}
