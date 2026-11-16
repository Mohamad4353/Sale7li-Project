<?php

namespace Tests\Feature\Marketplace;

use App\Models\Review;
use App\Models\ServiceCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MarketplacePerformanceTest extends TestCase
{
    use BuildsMarketplace, RefreshDatabase;

    /** The number of queries must not grow with the number of rows on the page. */
    private function queryCountFor(string $url): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get($url)->assertOk();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    public function test_service_discovery_query_count_is_flat(): void
    {
        ServiceCategory::factory()->count(3)->create(['is_active' => true]);
        $small = $this->queryCountFor(route('services.index'));

        ServiceCategory::factory()->count(9)->create(['is_active' => true]);
        $large = $this->queryCountFor(route('services.index'));

        $this->assertSame($small, $large, 'services.index should not issue more queries for more services.');
        $this->assertLessThanOrEqual(8, $large);
    }

    public function test_professional_discovery_query_count_is_flat(): void
    {
        $cats = ServiceCategory::factory()->count(4)->create(['is_active' => true])->all();

        foreach (range(1, 3) as $i) {
            $w = $this->professional([$cats[0], $cats[1]]);
            Review::factory()->count(2)->create(['worker_id' => $w->id]);
        }
        $small = $this->queryCountFor(route('workers.index'));

        foreach (range(1, 9) as $i) {
            $w = $this->professional([$cats[2], $cats[3]]);
            Review::factory()->count(2)->create(['worker_id' => $w->id]);
        }
        $large = $this->queryCountFor(route('workers.index'));

        $this->assertSame($small, $large, 'workers.index should not issue more queries for more professionals.');
        $this->assertLessThanOrEqual(12, $large);
    }

    public function test_worker_profile_query_count_is_flat_in_the_number_of_reviews(): void
    {
        $cat = ServiceCategory::factory()->create(['is_active' => true]);
        $worker = $this->professional([$cat]);

        Review::factory()->count(2)->create(['worker_id' => $worker->id]);
        $few = $this->queryCountFor(route('workers.show', $worker));

        Review::factory()->count(6)->create(['worker_id' => $worker->id]);
        $more = $this->queryCountFor(route('workers.show', $worker));

        $this->assertSame($few, $more);
    }
}
