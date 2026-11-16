<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * A customer's saved professionals. Uses the existing `favorites` pivot
 * (customer_id, worker_id) and User::favoriteWorkers() — no new table.
 */
class FavoriteController extends Controller
{
    public function index()
    {
        $workers = auth()->user()->favoriteWorkers()
            ->where('users.status', 'active')
            ->with([
                'workerProfile.availability',
                'workerServices' => fn ($q) => $q->where('is_active', true)->with('category:id,name,name_ar,slug,icon'),
            ])
            ->orderBy('users.name')
            ->paginate(12);

        return view('customer.favorites.index', compact('workers'));
    }

    /**
     * Add or remove a professional from the signed-in customer's favorites.
     * Idempotent per state; returns JSON for the fetch caller and falls back to
     * a redirect for a plain form post.
     */
    public function toggle(Request $request, User $worker)
    {
        abort_unless($worker->role === 'worker', 404);

        $customer = $request->user();
        $changed = $customer->favoriteWorkers()->toggle($worker->id);
        $favorited = ! empty($changed['attached']);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'favorited' => $favorited,
                'count' => $customer->favoriteWorkers()->count(),
            ]);
        }

        return back()->with('success', $favorited
            ? __('favorites.added')
            : __('favorites.removed'));
    }
}
