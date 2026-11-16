<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * The public, JSON-safe shape of one marketplace professional card — the ONLY
 * place the /professionals React-props + marketplace-API contract is defined, so
 * the Blade initial props and the API response can never drift apart. Never
 * serialises an Eloquent model directly. Deliberately omits email, phone,
 * tokens, payment/commission data and every id except the user id already
 * exposed in the profile URL. `availability` is computed server-side
 * (WorkerProfile::availabilityStatus); React only renders the resulting label.
 */
final class MarketplaceCard
{
    /**
     * @param  Collection<int, User>  $workers
     * @param  array<int, int>  $favoritedIds  worker ids the viewing customer has favourited
     * @return array<int, array<string, mixed>>
     */
    public static function collection(Collection $workers, array $favoritedIds = []): array
    {
        $favourite = array_flip($favoritedIds);

        return $workers
            ->map(fn (User $worker) => self::one($worker, isset($favourite[$worker->id])))
            ->all();
    }

    /**
     * Paginator → { data, meta } exactly as the API returns it. `meta` follows
     * Laravel's own paginator keys so the contract is unsurprising.
     *
     * @return array{data: array<int, array<string, mixed>>, meta: array<string, int>}
     */
    public static function page(LengthAwarePaginator $paginator, array $favoritedIds = []): array
    {
        return [
            'data' => self::collection($paginator->getCollection(), $favoritedIds),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }

    /** @return array<string, mixed> */
    public static function one(User $worker, bool $favorited = false): array
    {
        $profile = $worker->workerProfile;

        return [
            // Already public via the profile URL; the favourite endpoint is
            // keyed by it and enforces auth server-side.
            'id' => $worker->id,
            'name' => $worker->name,
            'verified' => true,
            'experience_years' => (int) ($profile->experience_years ?? 0),
            'rating' => [
                'average' => round((float) ($profile->rating_average ?? 0), 1),
                'count' => (int) ($profile->rating_count ?? 0),
            ],
            'services' => $worker->workerServices
                ->map(fn ($ws) => $ws->category?->localized_name)
                ->filter()
                ->values()
                ->all(),
            // Presentation-only, server-authoritative. Photo is served through
            // the existing authorized workers.photo route (no storage path).
            'availability' => $profile?->availabilityStatus() ?? 'unavailable',
            'service_radius_km' => $profile && $profile->service_radius_km ? (int) $profile->service_radius_km : null,
            'photo_url' => $profile && $profile->photo ? route('workers.photo', $worker) : null,
            'profile_url' => route('workers.show', $worker),
            'favorite_url' => route('customer.favorites.toggle', $worker),
            'favorited' => $favorited,
        ];
    }
}
