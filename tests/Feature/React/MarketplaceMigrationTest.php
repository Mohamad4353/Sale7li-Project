<?php

namespace Tests\Feature\React;

use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\WorkerProfile;
use App\Models\WorkerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 19 — /professionals is now the canonical React marketplace with the
 * full Blade marketplace as its no-JS fallback slot, and /explore is a
 * permanent-redirect compatibility route.
 */
class MarketplaceMigrationTest extends TestCase
{
    use RefreshDatabase;

    private function pro(string $name, string $category = 'Plumbing', array $profile = [], int $price = 40): User
    {
        $worker = User::factory()->worker()->create(['name' => $name, 'email' => 'p'.uniqid().'@example.test']);
        WorkerProfile::factory()->for($worker, 'user')->create([
            'verification_status' => 'verified',
            'rating_average' => 4.4, 'rating_count' => 9, 'experience_years' => 5, 'service_radius_km' => 15,
            ...$profile,
        ]);
        $slug = strtolower(str_replace(' ', '-', $category));
        $cid = ServiceCategory::firstOrCreate(['slug' => $slug], ['name' => $category, 'name_ar' => $category.' ع', 'base_price' => 30, 'is_active' => true])->id;
        WorkerService::create(['worker_id' => $worker->id, 'service_category_id' => $cid, 'price_from' => $price, 'is_active' => true]);

        return $worker;
    }

    private function props(string $html): array
    {
        $this->assertStringContainsString('data-react-root="MarketplaceSearch"', $html);
        preg_match('/data-props="([^"]*)"/', $html, $m);
        $decoded = json_decode(html_entity_decode($m[1] ?? '', ENT_QUOTES), true);
        $this->assertIsArray($decoded);

        return $decoded;
    }

    /* ---- React root + props ---- */

    public function test_professionals_has_exactly_one_marketplace_react_root_with_valid_props(): void
    {
        $this->pro('Rania Reliable');

        $html = $this->get(route('workers.index'))->assertOk()->getContent();
        $this->assertSame(1, substr_count($html, 'data-react-root='));

        $props = $this->props($html);
        $this->assertSame(
            ['q', 'category', 'sort', 'min_rating', 'min_price', 'max_price', 'page'],
            array_keys($props['initialFilters']),
        );
        $this->assertSame(['current_page', 'last_page', 'per_page', 'total'], array_keys($props['initialMeta']));
        $this->assertSame(12, $props['initialMeta']['per_page']);
    }

    public function test_initial_workers_and_meta_match_the_url_filters(): void
    {
        for ($i = 0; $i < 15; $i++) {
            $this->pro("Bulk {$i}", 'Plumbing', ['rating_average' => 5.0, 'rating_count' => 9]);
        }
        $this->pro('Cheap Carla', 'Plumbing', ['rating_average' => 5.0, 'rating_count' => 9], 20);

        $props = $this->props(
            $this->get(route('workers.index', ['category' => 'plumbing', 'min_rating' => 4, 'min_price' => 30, 'page' => 2]))
                ->assertOk()->getContent()
        );

        $this->assertSame(15, $props['initialMeta']['total']);   // Cheap Carla excluded by min_price
        $this->assertSame(2, $props['initialMeta']['current_page']);
        $this->assertSame(2, $props['initialFilters']['page']);
        $this->assertSame('4', $props['initialFilters']['min_rating']);
        $this->assertCount(3, $props['initialWorkers']);         // 15 total, 12/page → 3 on page 2
    }

    public function test_categories_sorts_and_ratings_are_localized(): void
    {
        $this->pro('Layla', 'Plumbing');

        $en = $this->props($this->get(route('workers.index'))->assertOk()->getContent());
        $this->assertSame('Plumbing', $en['categories'][0]['name']);
        $this->assertSame(__('workers.sort.rating'), collect($en['sorts'])->firstWhere('value', 'rating')['label']);
        $this->assertSame(__('workers.rating_stars', ['n' => 4]), collect($en['ratings'])->firstWhere('value', '4')['label']);

        $ar = $this->props($this->withSession(['locale' => 'ar'])->get(route('workers.index'))->assertOk()->getContent());
        $this->assertSame('Plumbing ع', $ar['categories'][0]['name']);
        $this->assertSame(__('workers.sort.rating', [], 'ar'), collect($ar['sorts'])->firstWhere('value', 'rating')['label']);
        $this->assertStringNotContainsString('Highest rated', json_encode($ar['sorts']));
    }

    public function test_no_sensitive_fields_in_props(): void
    {
        $this->pro('Silent Sam');
        $blob = json_encode($this->props($this->get(route('workers.index'))->assertOk()->getContent()));

        foreach (['email', 'phone', 'password', 'remember_token', 'commission', 'whish', 'payment', 'user_id'] as $needle) {
            $this->assertStringNotContainsStringIgnoringCase($needle, $blob, "leaked: {$needle}");
        }
        $this->assertStringNotContainsString('@example.test', $blob);
    }

    /* ---- Blade fallback stays complete + no-JS usable ---- */

    public function test_blade_fallback_renders_workers_links_pagination_and_all_filters(): void
    {
        for ($i = 0; $i < 15; $i++) {
            $this->pro("Grid {$i}");
        }

        $this->get(route('workers.index'))->assertOk()
            ->assertSee('Grid 0')                                     // worker card in the fallback grid
            ->assertSee(e(route('workers.show', User::first())), false) // profile link
            ->assertSee('page=2', false)                              // Blade paginator
            ->assertSee('name="q"', false)                            // search
            ->assertSee('name="sort"', false)                         // sort
            ->assertSee('name="category"', false)                     // category
            ->assertSee('name="min_rating"', false)                   // rating
            ->assertSee('name="min_price"', false)                    // price min  (added this phase)
            ->assertSee('name="max_price"', false);                   // price max
    }

    public function test_blade_fallback_price_filter_actually_filters_server_side(): void
    {
        $this->pro('Budget Bee', 'Plumbing', [], 30);
        $this->pro('Mid Mel', 'Plumbing', [], 80);
        $this->pro('Premium Pia', 'Plumbing', [], 500);

        $this->get(route('workers.index', ['min_price' => 50, 'max_price' => 200]))->assertOk()
            ->assertSee('Mid Mel')
            ->assertDontSee('Budget Bee')
            ->assertDontSee('Premium Pia');
    }

    public function test_the_react_page_still_serves_meaningful_seo_content(): void
    {
        $this->pro('Seo Sana');

        $this->get(route('workers.index'))->assertOk()
            ->assertSee('<h1', false)
            ->assertSee(__('workers.discover_title'))
            ->assertSee('Seo Sana')
            ->assertSee(e(route('workers.show', User::first())), false);
    }

    /* ---- /explore compatibility redirect ---- */

    public function test_explore_permanently_redirects_to_professionals(): void
    {
        $this->get('/explore')
            ->assertStatus(301)
            ->assertRedirect(route('workers.index'));
    }

    public function test_explore_redirect_preserves_every_supported_and_unrelated_parameter(): void
    {
        $query = [
            'q' => 'plumbing', 'category' => 'plumbing', 'sort' => 'rating',
            'min_rating' => '4', 'min_price' => '30', 'max_price' => '900', 'page' => '3',
            'utm_source' => 'newsletter',
        ];

        $response = $this->get('/explore?'.http_build_query($query))->assertStatus(301);
        $location = $response->headers->get('Location');

        parse_str(parse_url($location, PHP_URL_QUERY) ?? '', $got);
        $this->assertSame($query, $got);
        $this->assertStringContainsString('/professionals', $location);
    }

    public function test_the_obsolete_explore_cross_link_is_gone(): void
    {
        $this->pro('Layla');

        $this->get(route('workers.index'))->assertOk()
            ->assertDontSee('/explore', false);
    }
}
