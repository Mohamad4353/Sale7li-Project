<?php

namespace Tests\Feature\Marketplace;

use App\Models\ServiceCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceFilterTest extends TestCase
{
    use BuildsMarketplace, RefreshDatabase;

    public function test_service_category_filter_narrows_to_one_category(): void
    {
        $cleaning = ServiceCategory::factory()->create(['name' => 'Cleaning', 'slug' => 'cleaning', 'is_active' => true]);
        $plumbing = ServiceCategory::factory()->create(['name' => 'Plumbing', 'slug' => 'plumbing', 'is_active' => true]);

        $response = $this->get(route('services.index', ['category' => 'cleaning']))->assertOk();
        $response->assertSee(route('services.show', $cleaning), false);
        $response->assertDontSee(route('services.show', $plumbing), false);
    }

    public function test_service_price_filter_bounds_results(): void
    {
        $cheap = ServiceCategory::factory()->create(['slug' => 'cheap-fix', 'base_price' => 20, 'is_active' => true]);
        $premium = ServiceCategory::factory()->create(['slug' => 'premium-job', 'base_price' => 90, 'is_active' => true]);

        $response = $this->get(route('services.index', ['min_price' => 50, 'max_price' => 100]))->assertOk();
        $response->assertSee(route('services.show', $premium), false);
        $response->assertDontSee(route('services.show', $cheap), false);
    }

    public function test_reversed_price_bounds_are_swapped_not_rejected(): void
    {
        $mid = ServiceCategory::factory()->create(['slug' => 'mid-job', 'base_price' => 40, 'is_active' => true]);

        $this->get(route('services.index', ['min_price' => 100, 'max_price' => 10]))
            ->assertOk()
            ->assertSee(route('services.show', $mid), false);
    }

    public function test_worker_category_filter_only_shows_professionals_in_that_category(): void
    {
        $cleaning = ServiceCategory::factory()->create(['slug' => 'cleaning', 'is_active' => true]);
        $plumbing = ServiceCategory::factory()->create(['slug' => 'plumbing', 'is_active' => true]);

        $this->professional([$cleaning], name: 'Cleaner Carla');
        $this->professional([$plumbing], name: 'Plumber Pete');

        $this->get(route('workers.index', ['category' => 'cleaning']))
            ->assertOk()
            ->assertSee('Cleaner Carla')
            ->assertDontSee('Plumber Pete');
    }

    public function test_worker_rating_filter_excludes_lower_rated_and_unrated_professionals(): void
    {
        $cat = ServiceCategory::factory()->create(['is_active' => true]);

        $top = $this->professional([$cat], name: 'Five Star Sam');
        $this->reviewsFor($top, 3, 5);

        $mid = $this->professional([$cat], name: 'Three Star Tom');
        $this->reviewsFor($mid, 2, 3);

        $this->professional([$cat], name: 'Unrated Uma');

        $response = $this->get(route('workers.index', ['min_rating' => 4]));

        $response->assertOk();
        $response->assertSee('Five Star Sam');
        $response->assertDontSee('Three Star Tom');
        $response->assertDontSee('Unrated Uma');
    }

    public function test_combined_filters_apply_together(): void
    {
        $cleaning = ServiceCategory::factory()->create(['slug' => 'cleaning', 'is_active' => true]);
        $plumbing = ServiceCategory::factory()->create(['slug' => 'plumbing', 'is_active' => true]);

        $match = $this->professional([$cleaning], name: 'Match Molly');
        $this->reviewsFor($match, 4, 5);

        $wrongCategory = $this->professional([$plumbing], name: 'Wrong Category Will');
        $this->reviewsFor($wrongCategory, 4, 5);

        $lowRating = $this->professional([$cleaning], name: 'Low Rating Lee');
        $this->reviewsFor($lowRating, 4, 3);

        $response = $this->get(route('workers.index', ['category' => 'cleaning', 'min_rating' => 4]));

        $response->assertOk();
        $response->assertSee('Match Molly');
        $response->assertDontSee('Wrong Category Will');
        $response->assertDontSee('Low Rating Lee');
    }
}
