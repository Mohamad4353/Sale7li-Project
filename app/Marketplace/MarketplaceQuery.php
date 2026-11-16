<?php

namespace App\Marketplace;

use App\Models\User;
use App\Models\WorkerAvailability;
use App\Models\WorkerProfile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\Paginator;

/**
 * The single, canonical marketplace query — filtering + ordering + eager loads
 * for the professional discovery list. Used by BOTH the Blade /professionals
 * page (server-rendered fallback) and the read-only marketplace API, so the two
 * can never diverge. DiscoveryFilters is the canonical, validated view of the
 * query string. Read-only — no authorization decisions, no writes, no financial
 * data.
 */
final class MarketplaceQuery
{
    /** Card list page size. */
    public const PER_PAGE = 12;

    /** Allowed sort keys (first = default). */
    public const SORTS = ['recommended', 'rating', 'experience', 'jobs', 'newest', 'availability'];

    /**
     * A paginated set of verified, active professionals matching the filters,
     * eager-loaded for the card DTO.
     *
     * A page past the last one is clamped to the last page (never a redirect,
     * so no redirect loop; an empty result set has last_page = 1). This keeps
     * ?page=99999 returning a valid, non-empty final page and gives React a
     * current_page it can actually render.
     */
    public static function paginate(DiscoveryFilters $filters, int $perPage = self::PER_PAGE): LengthAwarePaginator
    {
        $requested = (int) Paginator::resolveCurrentPage();

        $page = self::builder($filters)->paginate($perPage)->withQueryString();

        if ($requested > 1 && $requested > $page->lastPage()) {
            $page = self::builder($filters)
                ->paginate($perPage, ['*'], 'page', $page->lastPage())
                ->withQueryString();
        }

        return $page;
    }

    /** The filtered + ordered + eager-loaded builder (no pagination applied). */
    public static function builder(DiscoveryFilters $filters): Builder
    {
        $ratingSub = WorkerProfile::select('rating_average')->whereColumn('user_id', 'users.id');
        $expSub = WorkerProfile::select('experience_years')->whereColumn('user_id', 'users.id');
        $jobsSub = WorkerProfile::select('completed_jobs')->whereColumn('user_id', 'users.id');
        // Portable "more available first" proxy: the count of open weekday rows.
        // Not a live "available right now" check (that needs non-portable
        // now()/weekday/time SQL); it mirrors what the card already shows —
        // no open rows => "unavailable".
        $availSub = WorkerAvailability::selectRaw('count(*)')
            ->whereColumn('worker_id', 'users.id')
            ->where('is_available', true);

        return User::query()
            ->verifiedProfessional()
            ->with([
                'workerProfile' => fn ($q) => $q->with('availability'),
                'workerServices' => fn ($q) => $q->where('is_active', true)
                    ->with('category:id,name,name_ar,slug,icon'),
            ])
            ->when($filters->hasSearch(), fn ($q) => $q->where(function ($w) use ($filters) {
                $like = '%'.$filters->q.'%';
                $w->where('name', 'like', $like)
                    ->orWhereHas('workerProfile', fn ($p) => $p
                        ->where('bio', 'like', $like)->orWhere('bio_ar', 'like', $like));
            }))
            ->when($filters->category, fn ($q) => $q->whereHas('workerServices', fn ($w) => $w
                ->where('is_active', true)
                ->whereHas('category', fn ($c) => $c->where('slug', $filters->category))))
            ->when($filters->minRating, fn ($q) => $q->whereHas('workerProfile', fn ($p) => $p
                ->where('rating_count', '>', 0)
                ->where('rating_average', '>=', $filters->minRating)))
            // Price is a superset the Blade /professionals page does not yet
            // expose: min/max on the professional's cheapest active service.
            ->when($filters->minPrice !== null, fn ($q) => $q->whereHas('workerServices', fn ($w) => $w
                ->where('is_active', true)->where('price_from', '>=', $filters->minPrice)))
            ->when($filters->maxPrice !== null, fn ($q) => $q->whereHas('workerServices', fn ($w) => $w
                ->where('is_active', true)->where('price_from', '<=', $filters->maxPrice)))
            ->tap(fn ($q) => match ($filters->sort) {
                'rating' => $q->orderByDesc((clone $ratingSub)),
                'experience' => $q->orderByDesc((clone $expSub)),
                'jobs' => $q->orderByDesc((clone $jobsSub)),
                'newest' => $q->latest('users.created_at'),
                'availability' => $q->orderByDesc((clone $availSub))->orderByDesc((clone $ratingSub))->orderBy('name'),
                default => $q->orderByDesc((clone $ratingSub))->orderByDesc((clone $jobsSub))->orderBy('name'),
            });
    }
}
