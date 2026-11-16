<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MarketplaceSearchRequest;
use App\Marketplace\DiscoveryFilters;
use App\Marketplace\MarketplaceQuery;
use App\Support\MarketplaceCard;
use Illuminate\Http\JsonResponse;

/**
 * Read-only marketplace API that backs the React marketplace on the canonical
 * /professionals page (WorkerController::index). Public — same visibility bar as
 * /professionals — and returns { data: [safe card], meta }. Validation is the
 * FormRequest; DiscoveryFilters is the canonical normaliser; MarketplaceQuery is
 * the canonical filter/sort/paginate. Laravel stays authoritative for
 * everything. (/explore is only a 301 redirect to /professionals.)
 *
 * Phase 20 caching: a weak ETag over the exact bytes returned, plus a private
 * Cache-Control. The response is deliberately NOT `public`: it runs in the web
 * middleware group (a session cookie is attached) and, for a signed-in
 * customer, carries per-customer `favorited` marking — a shared/CDN cache could
 * otherwise replay one user's cookie or favourites to another. `private` +
 * `Vary: Cookie` lets the browser cache and revalidate cheaply (304) with zero
 * cross-user risk. The ETag already varies by filters, page AND favourite
 * marking, so a guest ETag can never satisfy a customer's conditional request.
 */
class MarketplaceController extends Controller
{
    public function workers(MarketplaceSearchRequest $request): JsonResponse
    {
        $filters = DiscoveryFilters::fromRequest($request, MarketplaceQuery::SORTS);
        $page = MarketplaceQuery::paginate($filters);

        // One extra query, only for a signed-in customer, to mark cards the
        // React FavoriteButton should show as already saved.
        $favoritedIds = [];
        $user = $request->user();
        if ($user && $user->role === 'customer') {
            $favoritedIds = $user->favoriteWorkers()
                ->whereIn('users.id', $page->getCollection()->pluck('id'))
                ->pluck('users.id')
                ->all();
        }

        $response = response()->json(MarketplaceCard::page($page, $favoritedIds));

        // Weak validator over the response body (which already encodes the
        // filters, page and this viewer's favourite marking).
        $response->setEtag(md5((string) $response->getContent()), true);
        $response->headers->set('Vary', 'Cookie');

        if ($user === null) {
            // Fully static for a guest — safe to keep in the browser for 60s.
            $response->setPrivate();
            $response->setMaxAge(60);
        } else {
            // Favourites can change any time — always revalidate (cheap 304).
            $response->setPrivate();
            $response->setMaxAge(0);
            $response->headers->addCacheControlDirective('must-revalidate');
        }

        // Turns the response into a bodyless 304 when If-None-Match matches.
        $response->isNotModified($request);

        return $response;
    }
}
