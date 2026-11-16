<?php

namespace App\Http\Controllers;

use App\Models\ServiceCategory;
use App\Models\WorkerProfile;

class HomeController extends Controller
{
    public function index()
    {
        $categories = ServiceCategory::where('is_active', true)->orderBy('name')->get();

        // Featured professionals must meet the same public-visibility bar as the
        // marketplace (User::scopeVerifiedProfessional): verified profile, an
        // active account, and at least one active service. Otherwise a suspended
        // or not-yet-ready worker is featured here while `workers.show` 404s.
        $workers = WorkerProfile::with('user')
            ->where('verification_status', 'verified')
            ->whereHas('user', fn ($q) => $q->where('status', 'active'))
            ->whereHas('services', fn ($q) => $q->where('is_active', true))
            ->orderByDesc('rating_average')
            ->limit(4)
            ->get();

        return view('home', compact('categories', 'workers'));
    }

    public function dashboard()
    {
        return match (auth()->user()->role) {
            'customer' => redirect()->route('customer.dashboard'),
            'worker' => redirect()->route('worker.dashboard'),
            'admin' => redirect()->route('admin.dashboard'),
            // `users.role` is a DB enum, so this is only reachable via data
            // corruption or a future role. Deny cleanly instead of a 500.
            default => abort(403),
        };
    }
}
