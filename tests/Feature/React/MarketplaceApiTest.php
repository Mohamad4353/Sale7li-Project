<?php

namespace Tests\Feature\React;

use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\WorkerProfile;
use App\Models\WorkerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Phase 16 — GET /api/marketplace/workers. Read-only, public, same visibility
 * bar as /professionals, returns only the safe card DTO.
 */
class MarketplaceApiTest extends TestCase
{
    use RefreshDatabase;

    private function professional(string $name, string $category = 'Plumbing', array $profile = [], int $price = 40): User
    {
        $worker = User::factory()->worker()->create([
            'name' => $name,
            'email' => 'hidden.'.uniqid().'@example.test',
            'phone' => '+9613999888',
        ]);
        WorkerProfile::factory()->for($worker, 'user')->create([
            'verification_status' => 'verified',
            'rating_average' => 4.4,
            'rating_count' => 11,
            'experience_years' => 5,
            'service_radius_km' => 15,
            ...$profile,
        ]);
        $slug = strtolower(str_replace(' ', '-', $category));
        $categoryId = ServiceCategory::firstOrCreate(
            ['slug' => $slug],
            ['name' => $category, 'name_ar' => $category, 'base_price' => 30, 'is_active' => true],
        )->id;
        WorkerService::create([
            'worker_id' => $worker->id,
            'service_category_id' => $categoryId,
            'price_from' => $price,
            'is_active' => true,
        ]);

        return $worker;
    }

    public function test_a_guest_gets_the_public_card_list(): void
    {
        $worker = $this->professional('Rania Reliable');

        $this->getJson(route('api.marketplace.workers'))
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'name', 'verified', 'experience_years', 'rating' => ['average', 'count'], 'services', 'profile_url', 'favorite_url', 'favorited']]])
            ->assertJsonPath('data.0.name', 'Rania Reliable')
            ->assertJsonPath('data.0.favorited', false)
            ->assertJsonPath('data.0.id', $worker->id);
    }

    public function test_the_response_carries_no_sensitive_fields(): void
    {
        $this->professional('Silent Sam');

        $body = $this->getJson(route('api.marketplace.workers'))->assertOk()->getContent();

        foreach (['email', 'phone', 'password', 'remember_token', 'commission', 'whish', 'payment', 'user_id'] as $needle) {
            $this->assertStringNotContainsStringIgnoringCase($needle, $body, "leaked: {$needle}");
        }
        $this->assertStringNotContainsString('@example.test', $body);
    }

    public function test_it_filters_by_the_q_parameter(): void
    {
        $this->professional('Layla Plumber', 'Plumbing');
        $this->professional('Omar Electric', 'Electrical');

        $this->getJson(route('api.marketplace.workers', ['q' => 'layla']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Layla Plumber');
    }

    public function test_it_filters_by_the_category_slug(): void
    {
        $this->professional('Layla Plumber', 'Plumbing');
        $this->professional('Omar Electric', 'Electrical');

        $this->getJson(route('api.marketplace.workers', ['category' => 'electrical']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Omar Electric');
    }

    public function test_it_rejects_an_over_long_query(): void
    {
        $this->getJson(route('api.marketplace.workers', ['q' => str_repeat('a', 200)]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('q');
    }

    public function test_it_honours_marketplace_visibility_rules(): void
    {
        $this->professional('Visible Vera');

        $unverified = User::factory()->worker()->create(['name' => 'Unverified Uma']);
        WorkerProfile::factory()->for($unverified, 'user')->create(['verification_status' => 'pending']);
        WorkerService::create(['worker_id' => $unverified->id, 'service_category_id' => ServiceCategory::factory()->create()->id, 'price_from' => 40, 'is_active' => true]);

        $suspended = User::factory()->worker()->create(['name' => 'Suspended Sara', 'status' => 'suspended']);
        WorkerProfile::factory()->for($suspended, 'user')->create(['verification_status' => 'verified']);
        WorkerService::create(['worker_id' => $suspended->id, 'service_category_id' => ServiceCategory::factory()->create()->id, 'price_from' => 40, 'is_active' => true]);

        $this->getJson(route('api.marketplace.workers'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Visible Vera');
    }

    public function test_favorited_is_true_for_a_customer_who_saved_that_worker(): void
    {
        $worker = $this->professional('Bookmarked Bilal');
        $customer = User::factory()->customer()->create();

        // Use the existing favourite endpoint — no duplicate logic.
        $this->actingAs($customer)->postJson(route('customer.favorites.toggle', $worker))->assertOk();

        $this->actingAs($customer)->getJson(route('api.marketplace.workers'))
            ->assertOk()
            ->assertJsonPath('data.0.favorited', true);

        // A different customer does not see it as favourited.
        $this->actingAs(User::factory()->customer()->create())
            ->getJson(route('api.marketplace.workers'))
            ->assertOk()
            ->assertJsonPath('data.0.favorited', false);
    }

    public function test_it_is_rate_limited(): void
    {
        $this->professional('Throttle Tina');

        $response = $this->getJson(route('api.marketplace.workers'));
        $response->assertOk()->assertHeader('X-RateLimit-Limit', '60');
    }

    public function test_the_named_marketplace_limiter_blocks_the_61st_request_in_a_minute(): void
    {
        $this->professional('Throttle Tina');

        for ($i = 0; $i < 60; $i++) {
            $this->getJson(route('api.marketplace.workers'))->assertOk();
        }

        $this->getJson(route('api.marketplace.workers'))
            ->assertStatus(429)
            ->assertHeader('Retry-After');
    }

    /* ---- Phase 18: sort / rating / price / pagination / DTO ---- */

    public function test_the_card_dto_exposes_only_the_safe_extended_field_set(): void
    {
        $this->professional('Field Fiona');

        $card = $this->getJson(route('api.marketplace.workers'))->assertOk()->json('data.0');

        $this->assertSame(
            ['id', 'name', 'verified', 'experience_years', 'rating', 'services',
                'availability', 'service_radius_km', 'photo_url', 'profile_url', 'favorite_url', 'favorited'],
            array_keys($card),
        );
        $this->assertContains($card['availability'], ['available_today', 'available', 'busy', 'unavailable']);
        $this->assertSame(15, $card['service_radius_km']);
        $this->assertNull($card['photo_url']); // no photo uploaded → null, never a storage path
    }

    public function test_the_response_carries_pagination_meta(): void
    {
        for ($i = 0; $i < 15; $i++) {
            $this->professional("Pro {$i}");
        }

        $this->getJson(route('api.marketplace.workers'))
            ->assertOk()
            ->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'per_page', 'total']])
            ->assertJsonPath('meta.per_page', 12)
            ->assertJsonPath('meta.total', 15)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonCount(12, 'data');

        $this->getJson(route('api.marketplace.workers', ['page' => 2]))
            ->assertOk()
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonCount(3, 'data');
    }

    public function test_sort_by_rating_orders_highest_first(): void
    {
        $this->professional('Low Rated', 'Plumbing', ['rating_average' => 3.1, 'rating_count' => 5]);
        $this->professional('High Rated', 'Plumbing', ['rating_average' => 4.9, 'rating_count' => 5]);

        $names = collect($this->getJson(route('api.marketplace.workers', ['sort' => 'rating']))->assertOk()->json('data'))
            ->pluck('name')->all();

        $this->assertSame(['High Rated', 'Low Rated'], $names);
    }

    public function test_an_unknown_sort_falls_back_to_the_default_order_not_a_422(): void
    {
        // Phase 20: the API normalises like the Blade /professionals page — an
        // unknown sort is coerced to the default by DiscoveryFilters (the allow
        // list still lives there), never passed to an ORDER BY.
        $this->professional('Low Rated', 'Plumbing', ['rating_average' => 3.1, 'rating_count' => 5]);
        $this->professional('High Rated', 'Plumbing', ['rating_average' => 4.9, 'rating_count' => 5]);

        $names = collect($this->getJson(route('api.marketplace.workers', ['sort' => 'users.password; drop table users']))
            ->assertOk()->json('data'))->pluck('name')->all();

        // Default "recommended" order = rating desc, so High Rated leads.
        $this->assertSame(['High Rated', 'Low Rated'], $names);
    }

    public function test_min_rating_filters_out_lower_and_unrated_professionals(): void
    {
        $this->professional('Five Star', 'Plumbing', ['rating_average' => 5.0, 'rating_count' => 8]);
        $this->professional('Three Star', 'Plumbing', ['rating_average' => 3.0, 'rating_count' => 8]);
        $this->professional('Unrated', 'Plumbing', ['rating_average' => 0, 'rating_count' => 0]);

        $names = collect($this->getJson(route('api.marketplace.workers', ['min_rating' => 4]))->assertOk()->json('data'))
            ->pluck('name')->all();

        $this->assertSame(['Five Star'], $names);
    }

    public function test_an_out_of_set_min_rating_is_ignored_not_a_422(): void
    {
        // min_rating ∉ {3,4,5} is dropped by DiscoveryFilters (same as the page)
        // rather than 422-ing.
        $this->professional('Five Star', 'Plumbing', ['rating_average' => 5.0, 'rating_count' => 8]);
        $this->professional('Two Star', 'Plumbing', ['rating_average' => 2.0, 'rating_count' => 8]);

        $this->getJson(route('api.marketplace.workers', ['min_rating' => 2]))
            ->assertOk()
            ->assertJsonCount(2, 'data'); // filter ignored, both returned

        // A non-integer rating is still a shape error → 422.
        $this->getJson(route('api.marketplace.workers', ['min_rating' => 'lots']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('min_rating');
    }

    public function test_price_range_bounds_by_the_cheapest_active_service(): void
    {
        $this->professional('Budget', 'Plumbing', [], 30);
        $this->professional('Mid', 'Plumbing', [], 80);
        $this->professional('Premium', 'Plumbing', [], 500);

        $names = collect($this->getJson(route('api.marketplace.workers', ['min_price' => 50, 'max_price' => 200]))->assertOk()->json('data'))
            ->pluck('name')->all();

        $this->assertSame(['Mid'], $names);
    }

    public function test_a_negative_or_zero_min_price_is_dropped_not_a_422(): void
    {
        $this->professional('Budget', 'Plumbing', [], 30);
        $this->professional('Premium', 'Plumbing', [], 500);

        // -5 and 0 mean "no lower bound" (dropped); 999999 clamps to 100000 —
        // same as the Blade page.
        $this->getJson(route('api.marketplace.workers', ['min_price' => -5, 'max_price' => 999999]))
            ->assertOk()
            ->assertJsonCount(2, 'data');
        $this->getJson(route('api.marketplace.workers', ['min_price' => 0]))
            ->assertOk()
            ->assertJsonCount(2, 'data');

        // Non-numeric price is still a shape error → 422.
        $this->getJson(route('api.marketplace.workers', ['min_price' => 'cheap']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('min_price');
    }

    public function test_decimal_prices_are_honoured_not_truncated(): void
    {
        // worker_services.price_from is DECIMAL(10,2); 49.75 must not become 49.
        $under = $this->professional('Just Under', 'Plumbing', [], 0);
        $over = $this->professional('Just Over', 'Plumbing', [], 0);
        WorkerService::where('worker_id', $under->id)->update(['price_from' => 49.50]);
        WorkerService::where('worker_id', $over->id)->update(['price_from' => 49.90]);

        $names = collect($this->getJson(route('api.marketplace.workers', ['min_price' => 49.75]))
            ->assertOk()->json('data'))->pluck('name')->all();

        $this->assertSame(['Just Over'], $names); // 49.50 excluded, 49.90 kept — decimal respected
    }

    public function test_an_over_range_page_is_clamped_to_the_last_page(): void
    {
        for ($i = 0; $i < 15; $i++) {
            $this->professional("Pro {$i}");
        }

        $this->getJson(route('api.marketplace.workers', ['page' => 99999]))
            ->assertOk()
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonCount(3, 'data');

        // Empty result set: last_page is 1, so an over-range page clamps to 1.
        $this->getJson(route('api.marketplace.workers', ['q' => 'nobody-matches-this', 'page' => 99999]))
            ->assertOk()
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 1)
            ->assertJsonCount(0, 'data');

        // A non-numeric page coerces to 1 rather than 422.
        $this->getJson(route('api.marketplace.workers', ['page' => 'abc']))
            ->assertOk()
            ->assertJsonPath('meta.current_page', 1);
    }

    /* ---- Phase 20: HTTP caching (weak ETag + Cache-Control) ---- */

    public function test_guest_responses_are_browser_cacheable_with_a_weak_etag(): void
    {
        $this->professional('Cache Cara');

        $response = $this->getJson(route('api.marketplace.workers'))->assertOk();

        $etag = $response->headers->get('ETag');
        $this->assertNotNull($etag);
        $this->assertStringStartsWith('W/"', $etag); // weak validator

        $cc = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('private', $cc);   // never a shared cache — session cookie + favourites
        $this->assertStringContainsString('max-age=60', $cc);
        $this->assertStringNotContainsString('public', $cc);
        $this->assertStringContainsString('Cookie', (string) $response->headers->get('Vary'));
    }

    public function test_authenticated_responses_must_revalidate(): void
    {
        $this->professional('Cache Cara');
        $customer = User::factory()->customer()->create();

        $cc = $this->actingAs($customer)->getJson(route('api.marketplace.workers'))
            ->assertOk()->headers->get('Cache-Control');

        $this->assertStringContainsString('private', $cc);
        $this->assertStringContainsString('max-age=0', $cc);
        $this->assertStringContainsString('must-revalidate', $cc);
        $this->assertStringNotContainsString('public', $cc);
    }

    public function test_a_matching_etag_yields_a_304(): void
    {
        $this->professional('Cache Cara');

        $etag = $this->getJson(route('api.marketplace.workers'))->assertOk()->headers->get('ETag');

        $this->withHeader('If-None-Match', $etag)
            ->getJson(route('api.marketplace.workers'))
            ->assertStatus(304)
            ->assertNoContent(304);
    }

    public function test_a_guest_etag_never_satisfies_a_customers_conditional_request(): void
    {
        $worker = $this->professional('Cache Cara');

        // Capture the guest ETag while genuinely unauthenticated.
        $guestEtag = $this->getJson(route('api.marketplace.workers'))->assertOk()->headers->get('ETag');

        $customer = User::factory()->customer()->create();
        $this->actingAs($customer)->postJson(route('customer.favorites.toggle', $worker))->assertOk();

        // The customer's payload differs (favorited: true) so the guest ETag
        // must not produce a 304 — no cross-user cache reuse.
        $this->actingAs($customer)
            ->withHeader('If-None-Match', $guestEtag)
            ->getJson(route('api.marketplace.workers'))
            ->assertOk()
            ->assertJsonPath('data.0.favorited', true);
    }

    public function test_caching_headers_do_not_change_the_response_contract(): void
    {
        $this->professional('Contract Carl');

        $this->getJson(route('api.marketplace.workers'))
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'name', 'verified', 'experience_years', 'rating' => ['average', 'count'], 'services', 'availability', 'service_radius_km', 'photo_url', 'profile_url', 'favorite_url', 'favorited']], 'meta' => ['current_page', 'last_page', 'per_page', 'total']]);
    }

    public function test_query_count_is_flat_regardless_of_result_size(): void
    {
        $this->professional('Only One');
        $one = $this->countQueries();

        for ($i = 0; $i < 8; $i++) {
            $this->professional("Extra {$i}");
        }
        $many = $this->countQueries();

        $this->assertSame($one, $many, "API ran {$one} vs {$many} queries — availability/photo/services must stay eager-loaded.");
    }

    private function countQueries(): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->getJson(route('api.marketplace.workers'))->assertOk();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    public function test_all_filters_apply_together(): void
    {
        $target = $this->professional('Perfect Match', 'Plumbing', ['rating_average' => 4.8, 'rating_count' => 9], 90);
        $this->professional('Wrong Category', 'Electrical', ['rating_average' => 4.8, 'rating_count' => 9], 90);
        $this->professional('Too Cheap Rating', 'Plumbing', ['rating_average' => 3.0, 'rating_count' => 9], 90);
        $this->professional('Too Expensive', 'Plumbing', ['rating_average' => 4.8, 'rating_count' => 9], 900);

        $names = collect($this->getJson(route('api.marketplace.workers', [
            'q' => 'Match', 'category' => 'plumbing', 'sort' => 'rating', 'min_rating' => 4, 'min_price' => 50, 'max_price' => 300,
        ]))->assertOk()->json('data'))->pluck('name')->all();

        $this->assertSame(['Perfect Match'], $names);
        $this->assertNotNull($target->id);
    }
}
