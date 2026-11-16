<?php

namespace Tests\Feature\Worker;

use App\Models\Review;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 34 Step 1 — the customer-facing professional profile page (workers.show).
 * Presentation only: it reads existing profile/service/availability/reputation
 * data and drives the existing booking / favourite / messaging routes.
 */
class WorkerProfilePageTest extends TestCase
{
    use InteractsWithWorkers, RefreshDatabase;

    private function publicProfessional(array $userAttrs = [], ?ServiceCategory $category = null): User
    {
        $worker = $this->workerUser($userAttrs);
        $this->activeService($worker, $category);

        return $worker;
    }

    public function test_a_guest_can_view_an_eligible_public_professional(): void
    {
        $worker = $this->publicProfessional(['name' => 'Public Petra']);

        $this->get(route('workers.show', $worker))
            ->assertOk()
            ->assertSee('Public Petra');
    }

    public function test_a_customer_can_view_an_eligible_public_professional(): void
    {
        $worker = $this->publicProfessional(['name' => 'Public Petra']);

        $this->actingAs(User::factory()->customer()->create())
            ->get(route('workers.show', $worker))
            ->assertOk()
            ->assertSee('Public Petra');
    }

    public function test_a_missing_worker_returns_not_found(): void
    {
        $this->get(route('workers.show', 999999))->assertNotFound();
    }

    public function test_a_customer_account_is_not_exposed_as_a_professional(): void
    {
        $customer = User::factory()->customer()->create();

        $this->get(route('workers.show', $customer))->assertNotFound();
    }

    public function test_ineligible_professionals_are_not_exposed(): void
    {
        $unverified = $this->workerUser(['name' => 'Pending Pat'], verification: 'pending');
        $this->activeService($unverified);

        $suspended = $this->publicProfessional(['name' => 'Suspended Sam', 'status' => 'suspended']);

        $serviceless = $this->workerUser(['name' => 'Serviceless Sara']);

        $this->get(route('workers.show', $unverified))->assertNotFound();
        $this->get(route('workers.show', $suspended))->assertNotFound();
        $this->get(route('workers.show', $serviceless))->assertNotFound();
    }

    public function test_the_profile_renders_service_and_category_information(): void
    {
        $category = ServiceCategory::factory()->create(['name' => 'Emergency Plumbing', 'is_active' => true]);
        $worker = $this->publicProfessional(['name' => 'Skilled Sami'], $category);

        $this->get(route('workers.show', $worker))
            ->assertOk()
            ->assertSee(__('workers.services_offered'))
            ->assertSee('Emergency Plumbing')
            ->assertSee('href="'.route('services.show', $category).'"', false);
    }

    public function test_the_profile_renders_rating_and_reputation_when_available(): void
    {
        $worker = $this->publicProfessional(['name' => 'Rated Rima']);
        $reviewer = User::factory()->customer()->create(['name' => 'Karim Haddad']);

        Review::factory()->create([
            'worker_id' => $worker->id,
            'customer_id' => $reviewer->id,
            'rating' => 5,
            'comment' => 'Prompt and tidy work.',
        ]);
        $worker->workerProfile->update([
            'rating_average' => 4.5,
            'rating_count' => 8,
            'completed_jobs' => 12,
        ]);

        $this->get(route('workers.show', $worker))
            ->assertOk()
            ->assertSee('4.5')
            ->assertSee('(8)')
            ->assertSee('Prompt and tidy work.')
            ->assertSee('Karim')
            ->assertDontSee('Haddad')
            ->assertSee(__('workers.completed_jobs'));
    }

    public function test_the_page_renders_in_english(): void
    {
        $worker = $this->publicProfessional(['name' => 'English Emma']);

        $this->get(route('workers.show', $worker))
            ->assertOk()
            ->assertSee('<html lang="en" dir="ltr">', false)
            ->assertSee(__('workers.about', [], 'en'))
            ->assertSee(__('workers.services_offered', [], 'en'));
    }

    public function test_the_page_renders_in_arabic_with_rtl(): void
    {
        $worker = $this->publicProfessional(['name' => 'Arabic Amal']);

        $response = $this->withSession(['locale' => 'ar'])->get(route('workers.show', $worker));

        $response->assertOk();
        $response->assertSee('<html lang="ar" dir="rtl">', false);
        $response->assertSee(__('workers.about', [], 'ar'));
        $response->assertSee(__('workers.services_offered', [], 'ar'));
        $response->assertDontSee('workers.about');
    }

    public function test_customer_actions_use_existing_routes(): void
    {
        $category = ServiceCategory::factory()->create(['is_active' => true]);
        $worker = $this->publicProfessional(['name' => 'Actionable Adel'], $category);

        // Guest: the request CTA points at registration.
        $this->get(route('workers.show', $worker))
            ->assertOk()
            ->assertSee('href="'.route('register').'"', false);

        // Customer: the CTA books through the existing booking flow — now
        // carrying the professional's primary service as context — and the
        // favourite control posts to the existing toggle route.
        $this->actingAs(User::factory()->customer()->create())
            ->get(route('workers.show', $worker))
            ->assertOk()
            ->assertSee('href="'.route('customer.booking.create', ['category' => $category->slug]).'"', false)
            ->assertSee('action="'.route('customer.favorites.toggle', $worker).'"', false);
    }

    public function test_the_booking_cta_carries_the_professionals_primary_service(): void
    {
        $primary = ServiceCategory::factory()->create(['name' => 'Roof Repair', 'is_active' => true]);
        $worker = $this->publicProfessional(['name' => 'Context Carla'], $primary);
        $customer = User::factory()->customer()->create();

        // Main CTA + a per-service "request this" action both pre-select the category.
        $html = $this->actingAs($customer)->get(route('workers.show', $worker))->assertOk()->getContent();

        $target = route('customer.booking.create', ['category' => $primary->slug]);
        $this->assertGreaterThanOrEqual(2, substr_count($html, 'href="'.e($target).'"'));
        $this->assertStringContainsString(__('services.request_this'), $html);
    }

    public function test_a_guest_never_sees_the_booking_link_on_a_profile(): void
    {
        $worker = $this->publicProfessional(['name' => 'Guarded Gita']);

        $this->get(route('workers.show', $worker))
            ->assertOk()
            ->assertDontSee(route('customer.booking.create'), false)
            ->assertSee('href="'.route('register').'"', false);
    }
}
