<?php

namespace Tests\Feature\Marketplace;

use App\Models\ServiceCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 22 — /services gets the same clean canonical + rel=prev/next as
 * /professionals, built from the params /services actually honours
 * (q, category, price, sort) — never min_rating, never tracking params.
 */
class ServiceSeoTest extends TestCase
{
    use RefreshDatabase;

    private function categories(int $n): void
    {
        for ($i = 0; $i < $n; $i++) {
            ServiceCategory::factory()->create([
                'slug' => "svc-{$i}",
                'name' => "Service {$i}",
                'name_ar' => "خدمة {$i}",
                'base_price' => 30 + $i,
                'is_active' => true,
            ]);
        }
    }

    /** @return array<string,string> */
    private function headLinks(string $html): array
    {
        preg_match_all('/<link rel="(canonical|prev|next)" href="([^"]+)">/', $html, $m, PREG_SET_ORDER);

        return collect($m)->mapWithKeys(fn ($x) => [$x[1] => html_entity_decode($x[2], ENT_QUOTES)])->all();
    }

    public function test_clean_and_page_one_and_tracking_params_canonicalise_to_the_bare_path(): void
    {
        $this->categories(5);

        foreach (['/services', '/services?page=1', '/services?utm_source=n&gclid=x&sort=recommended'] as $url) {
            $links = $this->headLinks($this->get($url)->assertOk()->getContent());
            $this->assertSame(route('services.index'), $links['canonical'], "canonical wrong for {$url}");
            $this->assertArrayNotHasKey('prev', $links);
            $this->assertArrayNotHasKey('next', $links); // only 5 services, single page
        }
    }

    public function test_next_appears_on_the_first_page_when_more_pages_exist(): void
    {
        $this->categories(15);

        $links = $this->headLinks($this->get(route('services.index'))->assertOk()->getContent());

        $this->assertSame(route('services.index'), $links['canonical']);
        $this->assertArrayNotHasKey('prev', $links);
        $this->assertSame(route('services.index', ['page' => 2]), $links['next']);
    }

    public function test_canonical_and_prev_carry_supported_filters_and_exclude_rating_and_tracking(): void
    {
        $this->categories(15);

        $links = $this->headLinks(
            $this->get(route('services.index', [
                'category' => 'svc-1', 'utm_source' => 'x', 'min_rating' => 4, 'page' => 2,
            ]))->assertOk()->getContent()
        );

        // /services has no rating filter, so min_rating is not part of its canonical.
        $this->assertSame(route('services.index', ['category' => 'svc-1', 'page' => 2]), $links['canonical']);
        $this->assertSame(route('services.index', ['category' => 'svc-1']), $links['prev']); // page 1 → no ?page
        $this->assertStringNotContainsString('utm_source', $links['canonical']);
        $this->assertStringNotContainsString('min_rating', $links['canonical']);
    }

    public function test_exactly_one_canonical_link_is_emitted(): void
    {
        $this->categories(3);

        $this->assertSame(
            1,
            substr_count($this->get(route('services.index'))->assertOk()->getContent(), 'rel="canonical"')
        );
    }
}
