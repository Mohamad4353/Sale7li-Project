<?php

namespace App\Marketplace;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Canonical + rel=prev/next for a paginated discovery list. Shared by the
 * /professionals (WorkerController) and /services (ServiceController) pages so
 * the two emit the same, correct head links.
 *
 * The caller passes an already-normalised, allow-listed filter array (only the
 * query params that surface actually honours — no tracking params, no default
 * sort, no page). This class only assembles URLs: page 1 canonicalises to the
 * bare filtered path, prev/next are omitted at the first/last page, and the
 * page used is whatever the (possibly clamped) paginator reports.
 */
final class PaginationSeo
{
    /**
     * @param  array<string, mixed>  $filters  normalised supported query params
     * @return array{canonical: string, prev: ?string, next: ?string}
     */
    public static function links(string $routeName, array $filters, LengthAwarePaginator $page): array
    {
        $current = $page->currentPage();
        $last = $page->lastPage();

        $withPage = static fn (int $p): array => $p > 1 ? $filters + ['page' => $p] : $filters;

        return [
            'canonical' => route($routeName, $withPage($current)),
            'prev' => $current > 1 ? route($routeName, $withPage($current - 1)) : null,
            'next' => $current < $last ? route($routeName, $withPage($current + 1)) : null,
        ];
    }
}
