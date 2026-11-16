<?php

namespace Tests\Feature\Marketplace;

use App\Models\ServiceCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceLocalizationTest extends TestCase
{
    use BuildsMarketplace, RefreshDatabase;

    public function test_english_marketplace_renders_with_translated_controls(): void
    {
        ServiceCategory::factory()->create(['is_active' => true]);

        $response = $this->get(route('services.index'));

        $response->assertOk();
        $response->assertSee('<html lang="en" dir="ltr">', false);
        $response->assertSee(__('services.sort.recommended'));
        $response->assertSee(__('services.filters'));
    }

    public function test_arabic_marketplace_renders_rtl_with_translated_controls(): void
    {
        ServiceCategory::factory()->create(['is_active' => true]);

        $response = $this->withSession(['locale' => 'ar'])->get(route('services.index'));

        $response->assertOk();
        $response->assertSee('<html lang="ar" dir="rtl">', false);
        $response->assertSee('ترتيب حسب'); // services.sort_label
        $response->assertSee('عوامل التصفية'); // services.filters
    }

    public function test_professionals_page_is_localized_in_arabic(): void
    {
        $cat = ServiceCategory::factory()->create(['is_active' => true]);
        $this->professional([$cat]);

        $response = $this->withSession(['locale' => 'ar'])->get(route('workers.index'));

        $response->assertOk();
        $response->assertSee('<html lang="ar" dir="rtl">', false);
        $response->assertSee(__('workers.sort.rating', [], 'ar'));
    }

    public function test_service_detail_shows_localized_category_name(): void
    {
        $service = ServiceCategory::factory()->create([
            'name' => 'Plumbing', 'name_ar' => 'السباكة', 'is_active' => true,
        ]);

        $this->withSession(['locale' => 'ar'])->get(route('services.show', $service))
            ->assertOk()
            ->assertSee('السباكة')
            ->assertDontSee('>Plumbing<', false);
    }
}
