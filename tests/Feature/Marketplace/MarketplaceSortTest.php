<?php

namespace Tests\Feature\Marketplace;

use App\Models\ServiceCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceSortTest extends TestCase
{
    use BuildsMarketplace, RefreshDatabase;

    public function test_services_can_be_sorted_by_price_ascending_and_descending(): void
    {
        $cheap = ServiceCategory::factory()->create(['slug' => 'aaa-cheap', 'base_price' => 10, 'is_active' => true]);
        $pricey = ServiceCategory::factory()->create(['slug' => 'zzz-pricey', 'base_price' => 99, 'is_active' => true]);
        $cheapLink = route('services.show', $cheap);
        $priceyLink = route('services.show', $pricey);

        $low = $this->get(route('services.index', ['sort' => 'price_low']))->getContent();
        $this->assertLessThan(strpos($low, $priceyLink), strpos($low, $cheapLink));

        $high = $this->get(route('services.index', ['sort' => 'price_high']))->getContent();
        $this->assertLessThan(strpos($high, $cheapLink), strpos($high, $priceyLink));
    }

    public function test_professionals_can_be_sorted_by_rating(): void
    {
        $cat = ServiceCategory::factory()->create(['is_active' => true]);

        $high = $this->professional([$cat], name: 'Highly Rated Hana');
        $this->reviewsFor($high, 5, 5);

        $low = $this->professional([$cat], name: 'Lower Rated Lara');
        $this->reviewsFor($low, 5, 3);

        $html = $this->get(route('workers.index', ['sort' => 'rating']))->getContent();
        $this->assertLessThan(strpos($html, 'Lower Rated Lara'), strpos($html, 'Highly Rated Hana'));
    }

    public function test_professionals_can_be_sorted_by_experience(): void
    {
        $cat = ServiceCategory::factory()->create(['is_active' => true]);
        $this->professional([$cat], ['experience_years' => 2], name: 'Junior Jo');
        $this->professional([$cat], ['experience_years' => 18], name: 'Senior Sana');

        $html = $this->get(route('workers.index', ['sort' => 'experience']))->getContent();
        $this->assertLessThan(strpos($html, 'Junior Jo'), strpos($html, 'Senior Sana'));
    }

    public function test_an_arbitrary_sort_value_is_ignored_and_does_not_error(): void
    {
        ServiceCategory::factory()->create(['name' => 'Safe Service', 'is_active' => true]);

        $this->get(route('services.index', ['sort' => 'base_price); drop table users;--']))
            ->assertOk()
            ->assertSee('Safe Service');

        $this->get(route('workers.index', ['sort' => 'users.password']))->assertOk();

        // The tables are intact.
        $this->assertDatabaseCount('service_categories', 1);
    }
}
