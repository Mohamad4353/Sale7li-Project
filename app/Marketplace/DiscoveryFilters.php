<?php

namespace App\Marketplace;

use Illuminate\Http\Request;

/**
 * Normalised, allow-listed view of the marketplace query string.
 *
 * Everything the discovery pages (and, later, a recommendation layer) need to
 * know about what the customer asked for — with every value validated here so
 * controllers never pass raw request input into a query, an ORDER BY, or a
 * relationship name.
 *
 * This is the single canonical normaliser: the /professionals page, the
 * /services page and the read-only marketplace API all funnel raw input
 * through here, so an out-of-range, unknown or malformed value is clamped or
 * dropped in exactly one place rather than 422-ing on one surface and being
 * silently accepted on another. Prices are kept as floats end-to-end because
 * worker_services.price_from / service_categories.base_price are DECIMAL(10,2);
 * 49.99 stays 49.99 and is never truncated to 49.
 */
final class DiscoveryFilters
{
    /** @param list<string> $sortOptions */
    private function __construct(
        public readonly string $q,
        public readonly ?string $category,
        public readonly ?float $minPrice,
        public readonly ?float $maxPrice,
        public readonly ?int $minRating,
        public readonly string $sort,
        public readonly array $sortOptions,
    ) {}

    /**
     * @param  list<string>  $sortOptions  allowed sort keys for this surface, first = default
     */
    public static function fromRequest(Request $request, array $sortOptions): self
    {
        $sort = (string) $request->query('sort', '');
        if (! in_array($sort, $sortOptions, true)) {
            $sort = $sortOptions[0];
        }

        $min = self::floatOrNull($request->query('min_price'));
        $max = self::floatOrNull($request->query('max_price'));
        if ($min !== null && $max !== null && $min > $max) {
            [$min, $max] = [$max, $min];
        }

        $rating = self::intOrNull($request->query('min_rating'));
        if ($rating !== null && ! in_array($rating, [3, 4, 5], true)) {
            $rating = null;
        }

        $category = $request->query('category');
        $category = is_string($category) && $category !== '' ? mb_substr($category, 0, 120) : null;

        return new self(
            q: trim((string) $request->query('q', '')),
            category: $category,
            // A minimum price of 0 (or negative) is "no lower bound" — drop it so
            // it never lingers in the URL / canonical as a no-op filter. A
            // max_price of 0 is meaningful ("free only"), so it is kept.
            minPrice: $min !== null && $min > 0 ? min($min, 100000.0) : null,
            maxPrice: $max !== null ? max(0.0, min($max, 100000.0)) : null,
            minRating: $rating,
            sort: $sort,
            sortOptions: $sortOptions,
        );
    }

    public function hasSearch(): bool
    {
        return $this->q !== '';
    }

    public function isActive(): bool
    {
        return $this->hasSearch()
            || $this->category !== null
            || $this->minPrice !== null
            || $this->maxPrice !== null
            || $this->minRating !== null;
    }

    /** Query-string array for building "clear this filter" links. */
    public function except(string ...$keys): array
    {
        $all = array_filter([
            'q' => $this->q ?: null,
            'category' => $this->category,
            'min_price' => $this->minPrice,
            'max_price' => $this->maxPrice,
            'min_rating' => $this->minRating,
            'sort' => $this->sort !== $this->sortOptions[0] ? $this->sort : null,
        ], fn ($v) => $v !== null && $v !== '');

        foreach ($keys as $k) {
            unset($all[$k]);
        }

        return $all;
    }

    private static function intOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    /**
     * Price inputs are DECIMAL(10,2) in the schema, so a fractional value is a
     * legitimate filter, not garbage to truncate. Non-numeric input drops the
     * filter (the page has no 422 to fall back on); the API FormRequest also
     * rejects non-numeric price input with a 422 before it ever reaches here.
     */
    private static function floatOrNull(mixed $value): ?float
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }
}
