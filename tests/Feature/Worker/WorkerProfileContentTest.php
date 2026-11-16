<?php

namespace Tests\Feature\Worker;

use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression coverage for public professional-profile CONTENT that the existing
 * WorkerProfilePageTest renders but does not explicitly assert on: server-derived
 * prices, the localized bio, published availability, the experience / service-area
 * facts, and the deliberate omission of per-worker completion/cancellation
 * percentages from the public page. Presentation only — no production code here.
 */
class WorkerProfileContentTest extends TestCase
{
    use InteractsWithWorkers, RefreshDatabase;

    public function test_service_prices_are_server_derived_from_the_stored_rate(): void
    {
        $withRate = ServiceCategory::factory()->create(['name' => 'Wiring', 'base_price' => 30, 'is_active' => true]);
        $noRate = ServiceCategory::factory()->create(['name' => 'Painting', 'base_price' => 88, 'is_active' => true]);

        $worker = $this->workerUser(['name' => 'Priced Pia']);
        $this->activeService($worker, $withRate, ['price_from' => 65]);
        $this->activeService($worker, $noRate, ['price_from' => null]);

        $this->get(route('workers.show', $worker))
            ->assertOk()
            ->assertSee(__('services.from'))
            ->assertSee('$65.00')          // the professional's own price_from
            ->assertSee('$88.00');         // falls back to the category base_price
    }

    public function test_the_bio_is_shown_in_the_active_locale(): void
    {
        $worker = $this->workerUser(['name' => 'Bilingual Bishara'], profileAttrs: [
            'bio' => 'Twenty years rewiring Beirut homes.',
            'bio_ar' => 'عشرون عامًا في صيانة كهرباء المنازل.',
        ]);
        $this->activeService($worker);

        $this->get(route('workers.show', $worker))
            ->assertOk()
            ->assertSee('Twenty years rewiring Beirut homes.')
            ->assertDontSee('عشرون عامًا في صيانة كهرباء المنازل.');

        $this->withSession(['locale' => 'ar'])->get(route('workers.show', $worker))
            ->assertOk()
            ->assertSee('عشرون عامًا في صيانة كهرباء المنازل.')
            ->assertDontSee('Twenty years rewiring Beirut homes.');
    }

    public function test_a_bioless_professional_shows_the_localized_placeholder(): void
    {
        $worker = $this->workerUser(['name' => 'Quiet Qamar'], profileAttrs: ['bio' => null, 'bio_ar' => null]);
        $this->activeService($worker);

        $this->get(route('workers.show', $worker))
            ->assertOk()
            ->assertSee(__('workers.no_bio'))
            ->assertDontSee('workers.no_bio');
    }

    public function test_experience_and_service_radius_render_their_stored_values(): void
    {
        $worker = $this->workerUser(['name' => 'Seasoned Salwa'], profileAttrs: [
            'experience_years' => 9,
            'service_radius_km' => 25,
        ]);
        $this->activeService($worker);

        $this->get(route('workers.show', $worker))
            ->assertOk()
            ->assertSee(trans_choice('workers.experience_count', 9, ['count' => 9]))
            ->assertSee(__('workers.service_area_value', ['km' => 25]));
    }

    public function test_published_weekly_availability_is_listed_and_the_empty_case_has_a_placeholder(): void
    {
        $open = $this->workerUser(['name' => 'Open Omar']);
        $this->activeService($open);
        $this->availabilityRow($open, 0, ['start_time' => '08:30', 'end_time' => '16:30']);

        $this->get(route('workers.show', $open))
            ->assertOk()
            ->assertSee(__('workers.availability'))
            ->assertSee('08:30')
            ->assertSee('16:30');

        $closed = $this->workerUser(['name' => 'Unset Uma']);
        $this->activeService($closed);

        $this->get(route('workers.show', $closed))
            ->assertOk()
            ->assertSee(__('workers.availability_none'));
    }

    public function test_the_public_profile_never_exposes_per_worker_completion_or_cancellation_rates(): void
    {
        $worker = $this->workerUser(['name' => 'Metric Mira'], profileAttrs: [
            'completed_jobs' => 5,
            'completion_rate' => 63.00,
            'cancellation_rate' => 37.00,
            'rating_average' => 4.5,
            'rating_count' => 3,
        ]);
        $this->activeService($worker);

        $response = $this->get(route('workers.show', $worker))->assertOk();

        // The public trust signal is the completed-jobs COUNT + the star rating.
        $response->assertSee(__('workers.completed_jobs_count', ['count' => 5]));

        // The per-worker success/cancellation PERCENTAGES stay internal
        // (worker dashboard + admin analytics) — never on the public page.
        $response->assertDontSee('63%');
        $response->assertDontSee('63.00');
        $response->assertDontSee('37%');
        $response->assertDontSee('37.00');
        $response->assertDontSee(__('dashboard.stat_completion_rate'));
        $response->assertDontSee(__('dashboard.stat_cancellation_rate'));
    }

    public function test_a_customer_only_sees_the_favourite_and_per_service_request_actions_a_guest_does_not(): void
    {
        $category = ServiceCategory::factory()->create(['name' => 'Roofing', 'is_active' => true]);
        $worker = $this->workerUser(['name' => 'Action Aya']);
        $this->activeService($worker, $category);

        // Guest — no favourite form, no per-service booking button.
        $this->get(route('workers.show', $worker))
            ->assertOk()
            ->assertDontSee('action="'.route('customer.favorites.toggle', $worker).'"', false)
            ->assertDontSee(__('services.request_this'));

        // Customer — both are present.
        $this->actingAs(User::factory()->customer()->create())
            ->get(route('workers.show', $worker))
            ->assertOk()
            ->assertSee('action="'.route('customer.favorites.toggle', $worker).'"', false)
            ->assertSee(__('services.request_this'));
    }
}
