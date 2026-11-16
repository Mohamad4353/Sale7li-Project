<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * P14-07 — a lightweight top page-progress indicator lives in the layout once,
 * hidden from assistive tech, without disturbing existing navigation or the
 * data-autosubmit filter controls.
 */
class NavigationProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_layout_includes_a_single_page_progress_element(): void
    {
        $body = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('id="page-progress"', $body);
        $this->assertSame(1, substr_count($body, 'id="page-progress"'));
    }

    public function test_progress_element_is_hidden_from_assistive_tech(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('<div id="page-progress" aria-hidden="true"></div>', false);
    }

    public function test_existing_navigation_links_still_render(): void
    {
        $this->get('/')->assertOk()
            ->assertSee(route('login'), false)
            ->assertSee(route('register'), false);
    }

    public function test_data_autosubmit_filter_control_is_untouched(): void
    {
        $this->get(route('workers.index'))->assertOk()
            ->assertSee('data-autosubmit', false);
    }
}
