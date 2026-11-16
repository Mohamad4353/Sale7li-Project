<?php

namespace Tests\Feature\Ui;

use App\Models\ServiceCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 22 — the shared <x-filter-drawer> keeps its Alpine slide-over for JS
 * users AND gains a no-JS fallback: CSS keyed on `html:not(.js)` returns the
 * mobile panel to normal flow so the filters stay reachable without JavaScript.
 */
class FilterDrawerTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_drawer_renders_the_no_js_hook_and_keeps_the_alpine_slide_over(): void
    {
        ServiceCategory::factory()->create(['slug' => 'plumbing', 'is_active' => true]);

        $html = $this->get(route('services.index'))->assertOk()->getContent();

        // No-JS fallback hooks.
        $this->assertStringContainsString('data-filter-panel', $html);
        $this->assertStringContainsString('data-filter-trigger', $html);
        $this->assertStringContainsString('data-filter-dismiss', $html);

        // JS-on behaviour is untouched.
        $this->assertStringContainsString('x-data="{ open: false', $html);
        $this->assertStringContainsString('@click="open = true"', $html);
        $this->assertStringContainsString('x-trap="open && isMobile"', $html);

        // The filter fields themselves are always server-rendered (they live in
        // the slot), so they are present regardless of JS.
        $this->assertStringContainsString('name="min_price"', $html);
        $this->assertStringContainsString('name="category"', $html);
    }

    public function test_professionals_uses_the_same_drawer_with_the_no_js_hook(): void
    {
        $html = $this->get(route('workers.index'))->assertOk()->getContent();

        $this->assertStringContainsString('data-filter-panel', $html);
        $this->assertStringContainsString('name="min_rating"', $html); // rating filter only on /professionals
    }

    public function test_the_bundle_marks_js_and_the_stylesheet_has_the_no_js_rule(): void
    {
        $js = file_get_contents(resource_path('js/app.js'));
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString("document.documentElement.classList.add('js')", $js);
        $this->assertStringContainsString('html:not(.js) [data-filter-panel]', $css);
        $this->assertStringContainsString('html:not(.js) [data-filter-trigger]', $css);
    }
}
