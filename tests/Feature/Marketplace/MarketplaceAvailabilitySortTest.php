<?php

namespace Tests\Feature\Marketplace;

use App\Marketplace\MarketplaceQuery;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\WorkerAvailability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Phase 24 — the `availability` marketplace sort. A ranking enhancement only:
 * it uses the existing worker_availability rows (no new schema) and slots into
 * the existing DiscoveryFilters -> MarketplaceQuery pipeline. "More available"
 * = more weekday rows with is_available = true, a portable SQL proxy.
 */
class MarketplaceAvailabilitySortTest extends TestCase
{
    use BuildsMarketplace, RefreshDatabase;

    /** Give a professional N distinct open weekdays (0..N-1). */
    private function openDays(User $worker, int $count): void
    {
        for ($day = 0; $day < $count; $day++) {
            WorkerAvailability::factory()->create([
                'worker_id' => $worker->id,
                'day_of_week' => $day,
                'is_available' => true,
            ]);
        }
    }

    public function test_availability_is_an_allow_listed_sort_that_keeps_the_default_unchanged(): void
    {
        $this->assertContains('availability', MarketplaceQuery::SORTS);
        $this->assertSame('recommended', MarketplaceQuery::SORTS[0], 'the default sort must not move');
        foreach (['recommended', 'rating', 'experience', 'jobs', 'newest'] as $existing) {
            $this->assertContains($existing, MarketplaceQuery::SORTS, "existing sort {$existing} was dropped");
        }
    }

    public function test_sort_availability_is_accepted_on_the_page_and_the_api(): void
    {
        $cat = ServiceCategory::factory()->create(['is_active' => true]);
        $this->openDays($this->professional([$cat], name: 'Any One'), 3);

        $this->get(route('workers.index', ['sort' => 'availability']))->assertOk();

        $this->getJson(route('api.marketplace.workers', ['sort' => 'availability']))
            ->assertOk()
            ->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'per_page', 'total']]);
    }

    public function test_more_available_professionals_rank_first(): void
    {
        $cat = ServiceCategory::factory()->create(['is_active' => true]);

        $full = $this->professional([$cat], name: 'Wide Open Wafa');
        $this->openDays($full, 6);

        $some = $this->professional([$cat], name: 'Sometimes Sami');
        $this->openDays($some, 2);

        $none = $this->professional([$cat], name: 'Never Nour'); // zero worker_availability rows

        $names = collect(
            $this->getJson(route('api.marketplace.workers', ['sort' => 'availability']))->assertOk()->json('data')
        )->pluck('name')->all();

        $this->assertSame(['Wide Open Wafa', 'Sometimes Sami', 'Never Nour'], $names);
    }

    public function test_a_closed_day_row_does_not_count_as_availability(): void
    {
        $cat = ServiceCategory::factory()->create(['is_active' => true]);

        $open = $this->professional([$cat], name: 'Open Omar');
        $this->openDays($open, 2);

        $closed = $this->professional([$cat], name: 'Closed Carla');
        WorkerAvailability::factory()->closed()->count(5)->sequence(fn ($s) => ['day_of_week' => $s->index])
            ->create(['worker_id' => $closed->id]); // 5 rows, all is_available = false

        $names = collect(
            $this->getJson(route('api.marketplace.workers', ['sort' => 'availability']))->assertOk()->json('data')
        )->pluck('name')->all();

        $this->assertSame(['Open Omar', 'Closed Carla'], $names);
    }

    public function test_availability_sort_respects_existing_filters(): void
    {
        $plumbing = ServiceCategory::factory()->create(['slug' => 'plumbing', 'is_active' => true]);
        $electrical = ServiceCategory::factory()->create(['slug' => 'electrical', 'is_active' => true]);

        $plumberOpen = $this->professional([$plumbing], name: 'Plumber Open');
        $this->openDays($plumberOpen, 5);
        $electricianOpen = $this->professional([$electrical], name: 'Electrician Open');
        $this->openDays($electricianOpen, 5);

        $data = $this->getJson(route('api.marketplace.workers', ['sort' => 'availability', 'category' => 'plumbing']))
            ->assertOk()->json('data');

        $this->assertCount(1, $data);
        $this->assertSame('Plumber Open', $data[0]['name']);
    }

    public function test_availability_sort_paginates_and_preserves_the_query_string(): void
    {
        $cat = ServiceCategory::factory()->create(['is_active' => true]);
        for ($i = 0; $i < 15; $i++) {
            $this->openDays($this->professional([$cat], name: sprintf('Pro %02d', $i)), ($i % 6) + 1);
        }

        $page2 = $this->getJson(route('api.marketplace.workers', ['sort' => 'availability', 'page' => 2]))
            ->assertOk()
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.total', 15)
            ->json('data');

        $this->assertCount(3, $page2);

        $html = $this->get(route('workers.index', ['sort' => 'availability', 'page' => 2]))->assertOk()->getContent();
        $this->assertStringContainsString('sort=availability', $html); // paginator links keep the sort
        preg_match('/<link rel="canonical" href="([^"]+)">/', $html, $m);
        $this->assertStringContainsString('sort=availability', html_entity_decode($m[1] ?? '', ENT_QUOTES));
        $this->assertStringContainsString('page=2', html_entity_decode($m[1] ?? '', ENT_QUOTES));
    }

    public function test_an_arbitrary_or_injecting_sort_is_still_coerced_to_the_default(): void
    {
        $cat = ServiceCategory::factory()->create(['is_active' => true]);
        $this->openDays($this->professional([$cat], name: 'Safe Sam'), 1);

        $props = $this->reactProps($this->get(route('workers.index', ['sort' => '(select 1)); drop table worker_availability;--']))->assertOk()->getContent());
        $this->assertSame('recommended', $props['initialFilters']['sort']);

        $this->assertDatabaseCount('worker_availability', 1);
        $this->assertGreaterThan(0, User::count());
    }

    public function test_the_query_count_stays_flat_with_the_availability_sort(): void
    {
        $cat = ServiceCategory::factory()->create(['is_active' => true]);
        $this->openDays($this->professional([$cat]), 2);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->getJson(route('api.marketplace.workers', ['sort' => 'availability']))->assertOk();
        $one = count(DB::getQueryLog());

        for ($i = 0; $i < 8; $i++) {
            $this->openDays($this->professional([$cat]), ($i % 5) + 1);
        }
        DB::flushQueryLog();
        $this->getJson(route('api.marketplace.workers', ['sort' => 'availability']))->assertOk();
        $many = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame($one, $many, "availability sort ran {$one} vs {$many} queries — the ORDER BY sub-select must not add per-row queries");
    }

    /* ---- UI / localization contract (feeds React + Blade sort controls) ---- */

    public function test_the_sorts_prop_exposes_a_localised_availability_option_in_en_and_ar(): void
    {
        $cat = ServiceCategory::factory()->create(['is_active' => true]);
        $this->professional([$cat]);

        foreach (['en' => 'Most available', 'ar' => 'الأكثر توفرًا'] as $locale => $expected) {
            $props = $this->reactProps($this->withSession(['locale' => $locale])->get(route('workers.index'))->assertOk()->getContent());
            $option = collect($props['sorts'])->firstWhere('value', 'availability');

            $this->assertNotNull($option, "availability missing from the sorts prop in {$locale}");
            $this->assertSame($expected, $option['label']);
            $this->assertStringNotContainsString('workers.sort.', json_encode($props['sorts']), "raw sort key leaked in {$locale}");
        }
    }

    public function test_the_blade_no_js_sort_select_offers_availability_in_en_and_ar(): void
    {
        $cat = ServiceCategory::factory()->create(['is_active' => true]);
        $this->professional([$cat]);

        $en = $this->get(route('workers.index'))->assertOk();
        $en->assertSee('value="availability"', false);
        $en->assertSee(__('workers.sort.availability'));

        $ar = $this->withSession(['locale' => 'ar'])->get(route('workers.index'))->assertOk();
        $ar->assertSee('value="availability"', false);
        $ar->assertSee(__('workers.sort.availability', [], 'ar'), false);
    }

    private function reactProps(string $html): array
    {
        preg_match('/data-props="([^"]*)"/', $html, $m);

        return json_decode(html_entity_decode($m[1] ?? '', ENT_QUOTES), true) ?: [];
    }
}
