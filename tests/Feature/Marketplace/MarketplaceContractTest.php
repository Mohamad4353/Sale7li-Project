<?php

namespace Tests\Feature\Marketplace;

use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\WorkerProfile;
use App\Models\WorkerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 20 — one canonical normalisation contract shared by the Blade
 * /professionals page and the read-only API (out-of-range values are clamped,
 * not 422'd), decimal price filters are preserved, an over-range page is
 * clamped, and the paginated list emits a clean canonical + rel=prev/next.
 */
class MarketplaceContractTest extends TestCase
{
    use RefreshDatabase;

    private function pro(string $name, string $category = 'Plumbing', float $price = 40, array $profile = []): User
    {
        $worker = User::factory()->worker()->create(['name' => $name, 'email' => 'c'.uniqid().'@example.test']);
        WorkerProfile::factory()->for($worker, 'user')->create([
            'verification_status' => 'verified',
            'rating_average' => 4.4, 'rating_count' => 9, 'experience_years' => 4,
            ...$profile,
        ]);
        $slug = strtolower(str_replace(' ', '-', $category));
        $cid = ServiceCategory::firstOrCreate(['slug' => $slug], ['name' => $category, 'name_ar' => $category.' ع', 'base_price' => 30, 'is_active' => true])->id;
        WorkerService::create(['worker_id' => $worker->id, 'service_category_id' => $cid, 'price_from' => $price, 'is_active' => true]);

        return $worker;
    }

    private function props(string $html): array
    {
        preg_match('/data-props="([^"]*)"/', $html, $m);

        return json_decode(html_entity_decode($m[1] ?? '', ENT_QUOTES), true) ?: [];
    }

    /** @return array<string,string> the head <link rel="..."> hrefs, keyed by rel (entities decoded) */
    private function headLinks(string $html): array
    {
        preg_match_all('/<link rel="(canonical|prev|next)" href="([^"]+)">/', $html, $m, PREG_SET_ORDER);

        return collect($m)->mapWithKeys(fn ($x) => [$x[1] => html_entity_decode($x[2], ENT_QUOTES)])->all();
    }

    /* ---- validation-contract parity: normalise, never 422 ---- */

    public function test_the_page_normalises_out_of_range_price_exactly_like_the_api(): void
    {
        $this->pro('Range Rita');

        $props = $this->props(
            $this->get(route('workers.index', ['min_price' => -10, 'max_price' => 999999]))->assertOk()->getContent()
        );

        // A negative / zero minimum is "no lower bound" → dropped, not clamped to 0.
        $this->assertSame('', $props['initialFilters']['min_price']);
        // An over-range maximum still clamps to the ceiling.
        $this->assertSame('100000', $props['initialFilters']['max_price']);

        // API takes the same input without a 422.
        $this->getJson(route('api.marketplace.workers', ['min_price' => -10, 'max_price' => 999999]))
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_min_price_zero_is_treated_as_no_lower_bound_and_never_enters_the_url(): void
    {
        $this->pro('Zero Zed');

        $html = $this->get(route('workers.index', ['min_price' => 0]))->assertOk()->getContent();
        $props = $this->props($html);

        $this->assertSame('', $props['initialFilters']['min_price']);
        $this->assertSame(route('workers.index'), $this->headLinks($html)['canonical']); // canonical is the bare path

        // max_price = 0 stays — "free only" is a real filter.
        $maxProps = $this->props(
            $this->get(route('workers.index', ['max_price' => 0]))->assertOk()->getContent()
        );
        $this->assertSame('0', $maxProps['initialFilters']['max_price']);
    }

    public function test_the_page_coerces_an_unknown_sort_to_the_default(): void
    {
        $this->pro('Sort Sam');

        $props = $this->props(
            $this->get(route('workers.index', ['sort' => 'users.password); drop table']))->assertOk()->getContent()
        );

        $this->assertSame('recommended', $props['initialFilters']['sort']);
        $this->getJson(route('api.marketplace.workers', ['sort' => 'nonsense']))->assertOk();
    }

    public function test_the_page_ignores_an_out_of_set_min_rating(): void
    {
        $this->pro('Rating Rana', 'Plumbing', 40, ['rating_average' => 2.0, 'rating_count' => 5]);

        $props = $this->props(
            $this->get(route('workers.index', ['min_rating' => 2]))->assertOk()->getContent()
        );

        $this->assertSame('', $props['initialFilters']['min_rating']);
        $this->assertCount(1, $props['initialWorkers']); // filter dropped, pro still shown
    }

    public function test_decimal_price_filters_are_preserved_on_the_page(): void
    {
        $this->pro('Just Under', 'Plumbing', 49.50);
        $this->pro('Just Over', 'Plumbing', 49.90);

        $response = $this->get(route('workers.index', ['min_price' => 49.75]))->assertOk();
        $props = $this->props($response->getContent());

        $this->assertSame('49.75', $props['initialFilters']['min_price']); // not truncated to "49"
        $this->assertCount(1, $props['initialWorkers']);
        $this->assertSame('Just Over', $props['initialWorkers'][0]['name']);

        // The no-JS Blade grid filters the same way server-side.
        $response->assertSee('Just Over')->assertDontSee('Just Under');
    }

    /* ---- over-range pagination is clamped, not an empty page ---- */

    public function test_an_over_range_page_is_clamped_on_the_page(): void
    {
        for ($i = 0; $i < 15; $i++) {
            $this->pro("Grid {$i}");
        }

        $props = $this->props(
            $this->get(route('workers.index', ['page' => 99999]))->assertOk()->getContent()
        );

        $this->assertSame(2, $props['initialMeta']['current_page']);
        $this->assertSame(2, $props['initialMeta']['last_page']);
        $this->assertSame(2, $props['initialFilters']['page']);
        $this->assertCount(3, $props['initialWorkers']); // the real last page, not empty
    }

    public function test_an_over_range_page_on_an_empty_result_set_clamps_to_one(): void
    {
        $this->pro('Solo Sami');

        $props = $this->props(
            $this->get(route('workers.index', ['q' => 'nobody-matches', 'page' => 99999]))->assertOk()->getContent()
        );

        $this->assertSame(1, $props['initialMeta']['current_page']);
        $this->assertSame(1, $props['initialMeta']['last_page']);
        $this->assertCount(0, $props['initialWorkers']);
    }

    /* ---- canonical + rel=prev/next ---- */

    public function test_canonical_normalises_page_one_and_tracking_params_to_the_bare_path(): void
    {
        $this->pro('SEO Sana');

        foreach (['/professionals', '/professionals?page=1', '/professionals?utm_source=news&gclid=abc&sort=recommended'] as $url) {
            $links = $this->headLinks($this->get($url)->assertOk()->getContent());
            $this->assertSame(route('workers.index'), $links['canonical'], "canonical wrong for {$url}");
            $this->assertArrayNotHasKey('prev', $links, "unexpected rel=prev for {$url}");
        }
    }

    public function test_next_is_present_on_the_first_page_when_more_pages_exist_and_prev_is_not(): void
    {
        for ($i = 0; $i < 15; $i++) {
            $this->pro("Grid {$i}");
        }

        $links = $this->headLinks($this->get(route('workers.index'))->assertOk()->getContent());

        $this->assertSame(route('workers.index'), $links['canonical']);
        $this->assertArrayNotHasKey('prev', $links);
        $this->assertSame(route('workers.index', ['page' => 2]), $links['next']);
    }

    public function test_canonical_and_prev_next_carry_supported_filters_and_the_clamped_page(): void
    {
        for ($i = 0; $i < 15; $i++) {
            $this->pro("Grid {$i}", 'Plumbing');
        }

        $links = $this->headLinks(
            $this->get(route('workers.index', ['category' => 'plumbing', 'utm_source' => 'x', 'page' => 2]))->assertOk()->getContent()
        );

        $this->assertSame(route('workers.index', ['category' => 'plumbing', 'page' => 2]), $links['canonical']);
        $this->assertSame(route('workers.index', ['category' => 'plumbing']), $links['prev']); // page 1 → no ?page
        $this->assertArrayNotHasKey('next', $links); // already the last page
        $this->assertStringNotContainsString('utm_source', $links['canonical']);
    }

    public function test_canonical_uses_the_clamped_page_for_an_over_range_request(): void
    {
        for ($i = 0; $i < 15; $i++) {
            $this->pro("Grid {$i}");
        }

        $links = $this->headLinks($this->get(route('workers.index', ['page' => 99999]))->assertOk()->getContent());

        $this->assertSame(route('workers.index', ['page' => 2]), $links['canonical']);
    }

    /* ---- /explore compatibility keeps every parameter, garbage included ---- */

    public function test_explore_redirect_preserves_decimal_and_out_of_range_parameters(): void
    {
        $query = [
            'q' => 'plumb', 'min_price' => '49.99', 'max_price' => '999999',
            'sort' => 'DROP', 'min_rating' => '2', 'page' => 'abc', 'utm_source' => 'n',
        ];

        $location = $this->get('/explore?'.http_build_query($query))->assertStatus(301)->headers->get('Location');
        parse_str(parse_url($location, PHP_URL_QUERY) ?? '', $got);

        $this->assertSame($query, $got);
        $this->assertStringContainsString('/professionals', $location);
    }
}
