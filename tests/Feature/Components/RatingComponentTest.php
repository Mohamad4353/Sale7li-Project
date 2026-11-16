<?php

namespace Tests\Feature\Components;

use Tests\TestCase;

/**
 * P10-21 — one consistent rating presentation: a full 0–5 filled/empty frame,
 * correct zero handling (a 0.x average shows NO filled star), accessible text,
 * and a single-value mode for individual reviews.
 */
class RatingComponentTest extends TestCase
{
    private function render(string $blade): string
    {
        return (string) $this->blade($blade);
    }

    public function test_a_fractional_sub_one_average_shows_no_filled_star(): void
    {
        $html = $this->render('<x-rating :average="0.4" :count="3" />');

        $this->assertSame(0, substr_count($html, '★'));
        $this->assertSame(5, substr_count($html, '☆'));
        $this->assertStringContainsString('0.4', $html);
        $this->assertStringContainsString('(3)', $html);
    }

    public function test_no_reviews_renders_the_new_professional_state(): void
    {
        $html = $this->render('<x-rating :average="0" :count="0" />');

        $this->assertStringContainsString(__('reviews.new_professional'), $html);
        $this->assertSame(0, substr_count($html, '☆'));
    }

    public function test_an_aggregate_shows_a_five_position_frame_with_value_and_count(): void
    {
        $html = $this->render('<x-rating :average="4.2" :count="7" />');

        $this->assertSame(4, substr_count($html, '★'));
        $this->assertSame(1, substr_count($html, '☆'));
        $this->assertStringContainsString('4.2', $html);
        $this->assertStringContainsString('(7)', $html);
    }

    public function test_single_value_mode_has_no_count_and_an_out_of_five_label(): void
    {
        $html = $this->render('<x-rating :value="3" />');

        $this->assertSame(3, substr_count($html, '★'));
        $this->assertSame(2, substr_count($html, '☆'));
        $this->assertStringContainsString(__('reviews.rating_out_of', ['value' => 3]), $html);
        $this->assertStringNotContainsString('(1)', $html);
    }

    public function test_out_of_range_averages_are_clamped(): void
    {
        $high = $this->render('<x-rating :average="6" :count="1" />');
        $this->assertSame(5, substr_count($high, '★'));
        $this->assertSame(0, substr_count($high, '☆'));

        $low = $this->render('<x-rating :average="-2" :count="1" />');
        $this->assertSame(0, substr_count($low, '★'));
        $this->assertSame(5, substr_count($low, '☆'));
    }
}
