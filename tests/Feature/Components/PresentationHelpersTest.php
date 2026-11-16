<?php

namespace Tests\Feature\Components;

use Tests\TestCase;

/**
 * P10-22 — one shared date presentation (<x-date>) and the established money
 * component (<x-money>). Consistency only: no timezone or arithmetic change.
 */
class PresentationHelpersTest extends TestCase
{
    private function render(string $blade): string
    {
        return (string) $this->blade($blade);
    }

    public function test_date_renders_the_canonical_format_in_a_time_element(): void
    {
        $html = $this->render('<x-date value="2026-01-05" />');

        $this->assertStringContainsString('Jan 5, 2026', $html);
        $this->assertStringContainsString('<time datetime="2026-01-05', $html);
    }

    public function test_date_can_include_time_and_a_relative_suffix(): void
    {
        $withTime = $this->render('<x-date value="2026-01-05 14:30" :time="true" />');
        $this->assertStringContainsString('14:30', $withTime);

        $withDiff = $this->render('<x-date value="2026-01-05" :diff="true" />');
        $this->assertStringContainsString('ago', $withDiff);
    }

    public function test_a_missing_date_renders_the_shared_placeholder(): void
    {
        $this->assertStringContainsString(__('messages.none'), $this->render('<x-date :value="null" />'));
    }

    public function test_date_is_locale_aware(): void
    {
        $this->app->setLocale('ar');
        $html = $this->render('<x-date value="2026-01-05" />');

        $this->assertStringNotContainsString('Jan', $html);
    }

    public function test_money_component_formats_usd_unchanged(): void
    {
        $this->assertStringContainsString('$1,234.50', $this->render('<x-money amount="1234.5" />'));
    }
}
