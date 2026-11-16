<?php

namespace Tests\Feature\Marketplace;

use App\Models\ServiceCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceSearchTest extends TestCase
{
    use RefreshDatabase;

    /** The card grid links to /services/{slug}; the filter sidebar lists every
     * category, so we assert on the result-card link, not the raw name. */
    private function assertResultShown($response, ServiceCategory $c): void
    {
        $response->assertSee(route('services.show', $c), false);
    }

    private function assertResultHidden($response, ServiceCategory $c): void
    {
        $response->assertDontSee(route('services.show', $c), false);
    }

    public function test_search_returns_matching_services_and_excludes_the_rest(): void
    {
        $cleaning = ServiceCategory::factory()->create(['name' => 'Deep Cleaning', 'slug' => 'deep-cleaning', 'description' => 'Home cleaning', 'is_active' => true]);
        $electrical = ServiceCategory::factory()->create(['name' => 'Electrical Repair', 'slug' => 'electrical-repair', 'description' => 'Wiring and sockets', 'is_active' => true]);

        $response = $this->get(route('services.index', ['q' => 'clean']))->assertOk();

        $this->assertResultShown($response, $cleaning);
        $this->assertResultHidden($response, $electrical);
    }

    public function test_search_matches_the_arabic_name(): void
    {
        $plumbing = ServiceCategory::factory()->create(['name' => 'Plumbing', 'name_ar' => 'السباكة', 'slug' => 'plumbing', 'is_active' => true]);
        $painting = ServiceCategory::factory()->create(['name' => 'Painting', 'name_ar' => 'الدهان', 'slug' => 'painting', 'is_active' => true]);

        $response = $this->get(route('services.index', ['q' => 'السباكة']))->assertOk();

        $this->assertResultShown($response, $plumbing);
        $this->assertResultHidden($response, $painting);
    }

    public function test_inactive_services_never_appear(): void
    {
        $hidden = ServiceCategory::factory()->create(['name' => 'Hidden Service', 'slug' => 'hidden', 'is_active' => false]);

        $this->get(route('services.index', ['q' => 'Hidden']))
            ->assertOk()
            ->assertDontSee(route('services.show', $hidden), false);
    }

    public function test_pagination_preserves_the_search_term(): void
    {
        ServiceCategory::factory()->count(15)->create(['is_active' => true, 'name' => 'Cleaning Crew']);

        $response = $this->get(route('services.index', ['q' => 'Cleaning']))->assertOk();

        $response->assertSee('q=Cleaning', false);
        $response->assertSee('page=2', false);
    }

    public function test_whitespace_around_the_query_is_tolerated(): void
    {
        $ac = ServiceCategory::factory()->create(['name' => 'AC Servicing', 'slug' => 'ac-servicing', 'is_active' => true]);

        $this->get(route('services.index', ['q' => '  AC Servicing  ']))
            ->assertOk()
            ->assertSee(route('services.show', $ac), false);
    }
}
