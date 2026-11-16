<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Query-string validation for the public marketplace API. The endpoint is
 * read-only and open to guests, so authorization is always granted here — the
 * data it returns is already the public marketplace view. Parameter NAMES match
 * the Blade /professionals page (q, category, sort, min_rating, min_price,
 * max_price, page).
 *
 * These rules validate only the TYPE/SHAPE of each parameter (a string is a
 * string, a number is a number). Every range/enum decision — the sort allow
 * list, min_rating ∈ {3,4,5}, the 0–100000 price clamp, reversed price bounds,
 * an over-range page — is made once in DiscoveryFilters / MarketplaceQuery, so
 * the API and the Blade /professionals page (which has no FormRequest) treat an
 * out-of-range value identically: normalise it, never 422 on it. A genuinely
 * malformed value (non-numeric price, array where a string is expected,
 * over-long text) is still rejected here with a 422.
 */
class MarketplaceSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:120'],
            'sort' => ['nullable', 'string'],
            'min_rating' => ['nullable', 'integer'],
            'min_price' => ['nullable', 'numeric'],
            'max_price' => ['nullable', 'numeric'],
            // No `page` rule: Laravel's paginator already coerces any invalid
            // page (abc, 0, -5, 1.5, overflow) to 1, and MarketplaceQuery clamps
            // an over-range page to the last page — same as the Blade page.
        ];
    }
}
