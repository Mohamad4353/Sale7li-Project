<?php

namespace Tests\Feature\React;

use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\WorkerProfile;
use App\Models\WorkerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Marketplace category filter — API + the /professionals props that seed React.
 * Category filtering lives in MarketplaceQuery; this proves the wiring and the
 * safety of the category DTO. (Phase 19: props are read from /professionals,
 * which is now the canonical React marketplace.)
 */
class MarketplaceCategoryTest extends TestCase
{
    use RefreshDatabase;

    private function pro(string $name, string $category, string $slug): User
    {
        $worker = User::factory()->worker()->create(['name' => $name, 'email' => 'x'.uniqid().'@example.test']);
        WorkerProfile::factory()->for($worker, 'user')->create(['verification_status' => 'verified']);
        WorkerService::create([
            'worker_id' => $worker->id,
            'service_category_id' => ServiceCategory::factory()->create(['name' => $category, 'name_ar' => $category.' ع', 'slug' => $slug, 'is_active' => true])->id,
            'price_from' => 40,
            'is_active' => true,
        ]);

        return $worker;
    }

    private function props(string $html): array
    {
        preg_match('/data-props="([^"]*)"/', $html, $m);

        return json_decode(html_entity_decode($m[1] ?? '', ENT_QUOTES), true) ?: [];
    }

    /* ---- API ---- */

    public function test_api_returns_only_matches_for_a_category_slug(): void
    {
        $this->pro('Layla Plumber', 'Plumbing', 'plumbing');
        $this->pro('Omar Electric', 'Electrical', 'electrical');

        $this->getJson(route('api.marketplace.workers', ['category' => 'plumbing']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Layla Plumber');
    }

    public function test_api_treats_an_unknown_category_as_no_match_not_an_error(): void
    {
        $this->pro('Layla Plumber', 'Plumbing', 'plumbing');

        $this->getJson(route('api.marketplace.workers', ['category' => 'does-not-exist']))
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_api_rejects_an_over_long_category(): void
    {
        $this->getJson(route('api.marketplace.workers', ['category' => str_repeat('a', 200)]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('category');
    }

    public function test_api_q_and_category_apply_together(): void
    {
        $this->pro('Layla Plumber', 'Plumbing', 'plumbing');
        $this->pro('Layla Electric', 'Electrical', 'electrical');

        $this->getJson(route('api.marketplace.workers', ['q' => 'Layla', 'category' => 'electrical']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Layla Electric');
    }

    /* ---- /explore props ---- */

    public function test_explore_props_carry_a_safe_category_dto(): void
    {
        $this->pro('Layla Plumber', 'Plumbing', 'plumbing');

        $props = $this->props($this->get(route('workers.index'))->assertOk()->getContent());

        $this->assertArrayHasKey('categories', $props);
        $this->assertSame(['slug', 'name'], array_keys($props['categories'][0]));
        $this->assertSame('plumbing', $props['categories'][0]['slug']);
        $this->assertSame('Plumbing', $props['categories'][0]['name']);
    }

    public function test_explore_initial_filters_and_list_reflect_the_url_query(): void
    {
        $this->pro('Layla Plumber', 'Plumbing', 'plumbing');
        $this->pro('Omar Electric', 'Electrical', 'electrical');

        $props = $this->props(
            $this->get(route('workers.index', ['q' => 'Omar', 'category' => 'electrical']))->assertOk()->getContent()
        );

        $this->assertSame('Omar', $props['initialFilters']['q']);
        $this->assertSame('electrical', $props['initialFilters']['category']);
        $this->assertSame('recommended', $props['initialFilters']['sort']);
        $this->assertSame(1, $props['initialFilters']['page']);
        $this->assertCount(1, $props['initialWorkers']);
        $this->assertSame('Omar Electric', $props['initialWorkers'][0]['name']);
    }

    public function test_explore_category_dto_is_localised_in_arabic(): void
    {
        $this->pro('Layla Plumber', 'Plumbing', 'plumbing');

        $props = $this->props(
            $this->withSession(['locale' => 'ar'])->get(route('workers.index'))->assertOk()->getContent()
        );

        $this->assertSame('Plumbing ع', $props['categories'][0]['name']);
    }

    /* ---- /professionals is now the canonical React marketplace ---- */

    public function test_professionals_mounts_the_react_marketplace_and_keeps_the_blade_fallback(): void
    {
        $this->pro('Layla Plumber', 'Plumbing', 'plumbing');

        $this->get(route('workers.index', ['q' => 'lay', 'category' => 'plumbing']))->assertOk()
            ->assertSee('data-react-root="MarketplaceSearch"', false)   // React mount point
            ->assertSee('Layla Plumber')                                // Blade fallback still rendered
            ->assertSee(e(route('workers.show', User::first())), false); // real profile link in the fallback
    }
}
