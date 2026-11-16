<?php

namespace App\Support;

use App\Marketplace\DiscoveryFilters;
use App\Marketplace\MarketplaceQuery;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Builds the JSON-safe prop bag for the <MarketplaceSearch> React island on
 * /professionals. It is seeded from the SAME paginator the Blade fallback
 * renders, so React can show the exact server-rendered result without an
 * initial API request. Public marketplace data only — no emails, phones,
 * tokens, financial fields, or auth state.
 */
final class MarketplaceProps
{
    public static function build(DiscoveryFilters $filters, LengthAwarePaginator $page, ?User $user): array
    {
        $isCustomer = $user?->role === 'customer';

        $favoritedIds = [];
        if ($isCustomer) {
            $favoritedIds = $user->favoriteWorkers()
                ->whereIn('users.id', $page->getCollection()->pluck('id'))
                ->pluck('users.id')
                ->all();
        }

        $result = MarketplaceCard::page($page, $favoritedIds);

        $categories = ServiceCategory::active()
            ->orderBy('name')
            ->get(['id', 'name', 'name_ar', 'slug'])
            ->map(fn (ServiceCategory $c) => ['slug' => $c->slug, 'name' => $c->localized_name])
            ->all();

        return [
            'locale' => app()->getLocale(),
            'dir' => app()->getLocale() === 'ar' ? 'rtl' : 'ltr',
            'viewer' => ['isCustomer' => $isCustomer],
            'api' => ['workers' => route('api.marketplace.workers')],
            'categories' => $categories,
            'sorts' => array_map(
                fn ($s) => ['value' => $s, 'label' => __('workers.sort.'.$s)],
                MarketplaceQuery::SORTS,
            ),
            'ratings' => [
                ['value' => '', 'label' => __('workers.rating_any')],
                ['value' => '5', 'label' => __('workers.rating_stars', ['n' => 5])],
                ['value' => '4', 'label' => __('workers.rating_stars', ['n' => 4])],
                ['value' => '3', 'label' => __('workers.rating_stars', ['n' => 3])],
            ],
            'initialFilters' => [
                'q' => $filters->q,
                'category' => $filters->category ?? '',
                'sort' => $filters->sort,
                'min_rating' => $filters->minRating ? (string) $filters->minRating : '',
                'min_price' => $filters->minPrice !== null ? (string) $filters->minPrice : '',
                'max_price' => $filters->maxPrice !== null ? (string) $filters->maxPrice : '',
                'page' => $page->currentPage(),
            ],
            'initialWorkers' => $result['data'],
            'initialMeta' => $result['meta'],
            'strings' => [
                'search_label' => __('services.search'),
                'search_placeholder' => __('workers.discover_title'),
                'category_label' => __('services.category'),
                'all_categories' => __('services.all_categories'),
                'sort_label' => __('services.sort_label'),
                'rating_label' => __('workers.min_rating'),
                'price_label' => __('services.price'),
                'price_min' => __('services.min_price'),
                'price_max' => __('services.max_price'),
                'result_count' => __('marketplace.result_count'),
                'loading' => __('messages.loading'),
                'empty_title' => __('workers.none_title'),
                'empty_hint' => __('workers.none_hint'),
                'error' => __('marketplace.error'),
                'retry' => __('marketplace.retry'),
                'clear' => __('services.clear_filters'),
                'view_profile' => __('workers.view_profile'),
                'new_professional' => __('reviews.new_professional'),
                'verified' => __('status.verification.verified'),
                'years_experience' => __('marketplace.years_experience'),
                'service_area' => __('workers.service_area_km'),
                'page_of' => __('marketplace.page_of'),
                'go_to_page' => __('marketplace.go_to_page'),
                'prev_page' => __('pagination.previous'),
                'next_page' => __('pagination.next'),
                'pagination_nav' => __('pagination.nav'),
                'availability_available_today' => __('workers.availability_status.available_today'),
                'availability_available' => __('workers.availability_status.available'),
                'availability_busy' => __('workers.availability_status.busy'),
                'availability_unavailable' => __('workers.availability_status.unavailable'),
                'favorite_add' => __('marketplace.favorite_add'),
                'favorite_remove' => __('marketplace.favorite_remove'),
                'favorite_save' => __('marketplace.favorite_save'),
                'favorite_saved' => __('marketplace.favorite_saved'),
                'favorite_error' => __('marketplace.favorite_error'),
            ],
        ];
    }
}
