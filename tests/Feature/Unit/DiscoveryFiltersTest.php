<?php

namespace Tests\Feature\Unit;

use App\Marketplace\DiscoveryFilters;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Phase 28 — isolated contract tests for the marketplace input normaliser.
 *
 * DiscoveryFilters is the single place raw query-string input is validated
 * before it can reach an ORDER BY, a relationship name or a price comparison.
 * The feature tests exercise it through rendered pages; these pin the exact
 * normalised values with no DB round-trip.
 */
class DiscoveryFiltersTest extends TestCase
{
    /** @var list<string> */
    private const SORTS = ['recommended', 'rating', 'experience', 'jobs', 'newest', 'availability'];

    /**
     * @param  array<string, mixed>  $query
     * @param  list<string>  $sortOptions
     */
    private function filters(array $query, array $sortOptions = self::SORTS): DiscoveryFilters
    {
        return DiscoveryFilters::fromRequest(Request::create('/professionals', 'GET', $query), $sortOptions);
    }

    /* -------------------------------- sort -------------------------------- */

    public function test_unknown_empty_or_injecting_sort_falls_back_to_the_first_option(): void
    {
        $this->assertSame('recommended', $this->filters(['sort' => 'totally-made-up'])->sort);
        $this->assertSame('recommended', $this->filters(['sort' => ''])->sort);
        $this->assertSame('recommended', $this->filters(['sort' => '(select 1); drop table users;--'])->sort);
        $this->assertSame('recommended', $this->filters([])->sort);
    }

    public function test_each_allow_listed_sort_is_preserved(): void
    {
        foreach (self::SORTS as $sort) {
            $this->assertSame($sort, $this->filters(['sort' => $sort])->sort);
        }
    }

    public function test_the_first_sort_option_is_the_default_for_any_surface(): void
    {
        $this->assertSame('price_low', $this->filters(['sort' => 'nope'], ['price_low', 'price_high'])->sort);
        $this->assertSame('price_high', $this->filters(['sort' => 'price_high'], ['price_low', 'price_high'])->sort);
    }

    /* -------------------------------- price ------------------------------- */

    public function test_min_greater_than_max_price_is_swapped(): void
    {
        $f = $this->filters(['min_price' => '80', 'max_price' => '20']);
        $this->assertSame(20.0, $f->minPrice);
        $this->assertSame(80.0, $f->maxPrice);
    }

    public function test_zero_or_negative_min_price_is_dropped_but_zero_max_price_is_kept(): void
    {
        $this->assertNull($this->filters(['min_price' => '0'])->minPrice);
        $this->assertNull($this->filters(['min_price' => '-5'])->minPrice);
        $this->assertSame(0.0, $this->filters(['max_price' => '0'])->maxPrice);   // "free only" is meaningful
        $this->assertSame(0.0, $this->filters(['max_price' => '-10'])->maxPrice); // clamped up to 0
    }

    public function test_prices_are_clamped_to_the_ceiling(): void
    {
        $this->assertSame(100000.0, $this->filters(['min_price' => '999999'])->minPrice);
        $this->assertSame(100000.0, $this->filters(['max_price' => '999999'])->maxPrice);
    }

    public function test_fractional_price_is_preserved_not_truncated(): void
    {
        $this->assertSame(49.99, $this->filters(['min_price' => '49.99'])->minPrice);
    }

    public function test_non_numeric_or_absent_price_drops_the_filter(): void
    {
        $this->assertNull($this->filters(['min_price' => 'cheap'])->minPrice);
        $this->assertNull($this->filters(['max_price' => ''])->maxPrice);
        $this->assertNull($this->filters([])->minPrice);
        $this->assertNull($this->filters([])->maxPrice);
    }

    /* ------------------------------- rating ------------------------------- */

    public function test_min_rating_accepts_only_three_four_five(): void
    {
        $this->assertSame(3, $this->filters(['min_rating' => '3'])->minRating);
        $this->assertSame(4, $this->filters(['min_rating' => '4'])->minRating);
        $this->assertSame(5, $this->filters(['min_rating' => '5'])->minRating);
        $this->assertNull($this->filters(['min_rating' => '2'])->minRating);
        $this->assertNull($this->filters(['min_rating' => '6'])->minRating);
        $this->assertNull($this->filters(['min_rating' => '0'])->minRating);
        $this->assertNull($this->filters(['min_rating' => 'good'])->minRating);
    }

    /* ----------------------------- category + q -------------------------- */

    public function test_category_is_trimmed_to_120_chars_and_empty_becomes_null(): void
    {
        $this->assertSame('plumbing', $this->filters(['category' => 'plumbing'])->category);
        $this->assertNull($this->filters(['category' => ''])->category);
        $this->assertNull($this->filters([])->category);
        $this->assertSame(120, mb_strlen((string) $this->filters(['category' => str_repeat('a', 200)])->category));
    }

    public function test_non_string_category_is_dropped(): void
    {
        $this->assertNull($this->filters(['category' => ['x', 'y']])->category);
    }

    public function test_search_term_is_trimmed(): void
    {
        $this->assertSame('leaky tap', $this->filters(['q' => '  leaky tap  '])->q);
        $this->assertSame('', $this->filters([])->q);
    }

    /* -------------------------- helper predicates ----------------------- */

    public function test_has_search_and_is_active(): void
    {
        $this->assertFalse($this->filters([])->hasSearch());
        $this->assertFalse($this->filters([])->isActive());
        $this->assertFalse($this->filters(['sort' => 'rating'])->isActive(), 'sort alone is not an active filter');

        $this->assertTrue($this->filters(['q' => 'x'])->hasSearch());
        $this->assertTrue($this->filters(['q' => 'x'])->isActive());
        $this->assertTrue($this->filters(['category' => 'plumbing'])->isActive());
        $this->assertTrue($this->filters(['min_price' => '10'])->isActive());
        $this->assertTrue($this->filters(['max_price' => '0'])->isActive(), '0.0 is a real upper bound');
        $this->assertTrue($this->filters(['min_rating' => '4'])->isActive());
    }

    public function test_except_returns_active_filters_minus_named_keys_and_omits_default_sort(): void
    {
        $f = $this->filters(['q' => 'tap', 'category' => 'plumbing', 'sort' => 'rating', 'min_rating' => '4']);

        $this->assertSame(
            ['q' => 'tap', 'category' => 'plumbing', 'min_rating' => 4, 'sort' => 'rating'],
            $f->except(),
        );
        $this->assertArrayNotHasKey('category', $f->except('category'));
        $this->assertArrayNotHasKey('sort', $this->filters(['q' => 'tap'])->except(), 'default sort is omitted');
        $this->assertSame([], $this->filters([])->except());
    }
}
