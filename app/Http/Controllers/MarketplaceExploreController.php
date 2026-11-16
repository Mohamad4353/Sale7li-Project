<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Phase 19 — /professionals is now the canonical React marketplace. /explore is
 * kept only as a permanent-redirect compatibility route so old links / bookmarks
 * keep working; every query parameter (q, category, sort, min_rating, min_price,
 * max_price, page — and any unrelated ones) is carried across untouched.
 */
class MarketplaceExploreController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        return redirect()->route('workers.index', $request->query(), 301);
    }
}
