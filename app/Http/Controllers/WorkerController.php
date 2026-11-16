<?php

namespace App\Http\Controllers;

use App\Marketplace\DiscoveryFilters;
use App\Marketplace\MarketplaceQuery;
use App\Marketplace\PaginationSeo;
use App\Models\Review;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Support\MarketplaceProps;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Public marketplace discovery for verified professionals. Read-only. Email and
 * addresses are never loaded or rendered; the profile page uses the phone only
 * to build a "Contact us on WhatsApp" click-to-chat link (the number is placed in
 * the link, never shown as text), and only when the professional has one.
 */
class WorkerController extends Controller
{
    public function index(Request $request)
    {
        // One canonical marketplace pipeline: DiscoveryFilters → MarketplaceQuery
        // → MarketplaceCard, shared by the Blade fallback below AND the API.
        $filters = DiscoveryFilters::fromRequest($request, MarketplaceQuery::SORTS);
        $workers = MarketplaceQuery::paginate($filters);

        $categories = ServiceCategory::active()->orderBy('name')->get(['id', 'name', 'name_ar', 'slug', 'icon']);

        // Props for the <MarketplaceSearch> React island, seeded from the SAME
        // paginator the fallback renders so React needs no initial API request.
        $reactProps = MarketplaceProps::build($filters, $workers, $request->user());

        return view('workers.index', compact('workers', 'categories', 'filters', 'reactProps') + [
            // Canonical + rel=prev/next from the normalised supported filters
            // (no tracking params, no default sort) and the clamped page.
            'seo' => PaginationSeo::links('workers.index', $filters->except(), $workers),
        ]);
    }

    public function show(User $worker)
    {
        abort_unless($worker->role === 'worker' && $worker->status === 'active', 404);

        $worker->load([
            'workerProfile.availability',
            'workerProfile.portfolioItems' => fn ($q) => $q
                ->where('is_visible', true)
                ->orderBy('sort_order')
                ->orderBy('id'),
            'workerServices' => fn ($q) => $q->where('is_active', true)->with('category'),
        ]);

        abort_unless(
            $worker->workerProfile
                && $worker->workerProfile->verification_status === 'verified'
                && $worker->workerServices->isNotEmpty(),
            404
        );

        $reviews = Review::where('worker_id', $worker->id)
            ->with('customer:id,name')
            ->latest()
            ->paginate(8);

        // "Busy" = mid-job right now. One bounded exists() query.
        $busy = $worker->workerRequests()
            ->whereIn('status', ['scheduled', 'on_the_way', 'in_progress'])
            ->exists();

        return view('workers.show', compact('worker', 'reviews', 'busy'));
    }

    /**
     * Serves a professional's profile photo from the private `local` disk.
     * Public only while the professional is marketplace-visible; otherwise just
     * the professional themselves (e.g. previewing a freshly uploaded photo on
     * their own profile page). Follows AttachmentController's security model —
     * private storage, no predictable public URL, authorization before stream.
     */
    public function photo(User $worker): StreamedResponse
    {
        abort_unless($worker->role === 'worker', 404);

        $profile = $worker->workerProfile;
        abort_if($profile === null || ! $profile->photo, 404);

        $isOwner = auth()->id() === $worker->id;
        $isPublic = $worker->status === 'active'
            && $profile->verification_status === 'verified'
            && $worker->workerServices()->where('is_active', true)->exists();

        abort_unless($isOwner || $isPublic, 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($profile->photo), 404);

        return $disk->response($profile->photo);
    }

    /**
     * Serves one portfolio image from the private `local` disk. Same security
     * model as photo(): visible to anyone only while the item is `is_visible`
     * AND the professional is marketplace-visible; otherwise only the
     * professional themselves. The item must belong to {worker}.
     */
    public function portfolioImage(User $worker, int $portfolioItem): StreamedResponse
    {
        abort_unless($worker->role === 'worker', 404);

        $item = $worker->portfolioItems()->find($portfolioItem);
        abort_if($item === null, 404);

        $isOwner = auth()->id() === $worker->id;
        $isPublic = $item->is_visible
            && $worker->status === 'active'
            && $worker->workerProfile?->verification_status === 'verified'
            && $worker->workerServices()->where('is_active', true)->exists();

        abort_unless($isOwner || $isPublic, 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($item->image_path), 404);

        return $disk->response($item->image_path);
    }
}
