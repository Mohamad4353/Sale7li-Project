<?php

namespace Tests\Feature;

use App\Models\ServiceCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Worker\InteractsWithWorkers;
use Tests\TestCase;

/**
 * P14-09 / P14-17 — the homepage professional cards are real navigation links
 * to the existing public profile route, and their rating uses <x-rating> with
 * the real stored figures (never an invented score).
 */
class HomepageProfessionalCardsTest extends TestCase
{
    use InteractsWithWorkers;
    use RefreshDatabase;

    public function test_professional_card_links_to_the_public_profile_route(): void
    {
        $category = ServiceCategory::factory()->create(['is_active' => true]);
        $worker = $this->workerUser(profileAttrs: ['rating_average' => 4.5, 'rating_count' => 8]);
        $this->activeService($worker, $category);

        $this->get('/')
            ->assertOk()
            ->assertSee('href="'.route('workers.show', $worker).'"', false);
    }

    public function test_real_rating_data_is_rendered(): void
    {
        $category = ServiceCategory::factory()->create(['is_active' => true]);
        $worker = $this->workerUser(profileAttrs: ['rating_average' => 4.5, 'rating_count' => 8]);
        $this->activeService($worker, $category);

        $this->get('/')
            ->assertOk()
            ->assertSee('4.5')
            ->assertSee('(8)');
    }

    public function test_a_professional_with_no_reviews_shows_no_invented_rating(): void
    {
        $category = ServiceCategory::factory()->create(['is_active' => true]);
        $worker = $this->workerUser(profileAttrs: ['rating_average' => 0, 'rating_count' => 0]);
        $this->activeService($worker, $category);

        $this->get('/')
            ->assertOk()
            ->assertSee(__('reviews.new_professional'))
            ->assertDontSee('★★★');
    }

    public function test_only_verified_professionals_are_listed(): void
    {
        $category = ServiceCategory::factory()->create(['is_active' => true]);
        $pending = $this->workerUser(userAttrs: ['name' => 'Unverified Pat'], verification: 'pending');
        $this->activeService($pending, $category);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('Unverified Pat');
    }
}
