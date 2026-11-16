<?php

namespace Tests\Feature\Marketplace;

use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\WorkerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceAuthorizationTest extends TestCase
{
    use BuildsMarketplace, RefreshDatabase;

    public function test_public_worker_profile_never_exposes_private_contact_details(): void
    {
        $cat = ServiceCategory::factory()->create(['is_active' => true]);
        $worker = $this->professional([$cat]);
        $worker->update(['email' => 'private-worker@example.com', 'phone' => '+961 71 999 888']);

        $response = $this->get(route('workers.show', $worker));

        $response->assertOk();
        $response->assertDontSee('private-worker@example.com');
        $response->assertDontSee('+961 71 999 888');
    }

    public function test_worker_discovery_listing_does_not_leak_emails_or_phones(): void
    {
        $cat = ServiceCategory::factory()->create(['is_active' => true]);
        $worker = $this->professional([$cat]);
        $worker->update(['email' => 'listing-leak@example.com', 'phone' => '+961 70 111 222']);

        $response = $this->get(route('workers.index'));

        $response->assertOk();
        $response->assertDontSee('listing-leak@example.com');
        $response->assertDontSee('+961 70 111 222');
    }

    public function test_unverified_worker_is_not_discoverable(): void
    {
        $cat = ServiceCategory::factory()->create(['is_active' => true]);
        $worker = User::factory()->worker()->create(['name' => 'Pending Pat']);
        WorkerProfile::factory()->for($worker, 'user')->create(['verification_status' => 'pending']);

        $this->get(route('workers.index'))->assertOk()->assertDontSee('Pending Pat');
        $this->get(route('workers.show', $worker))->assertNotFound();
    }

    public function test_suspended_verified_worker_is_excluded_from_discovery(): void
    {
        $cat = ServiceCategory::factory()->create(['is_active' => true]);
        $worker = $this->professional([$cat], name: 'Suspended Sue');
        $worker->update(['status' => 'suspended']);

        $this->get(route('workers.index'))->assertOk()->assertDontSee('Suspended Sue');
    }

    public function test_a_customer_account_cannot_be_viewed_as_a_professional(): void
    {
        $customer = User::factory()->customer()->create();

        $this->get(route('workers.show', $customer))->assertNotFound();
    }

    public function test_reviews_show_only_the_reviewer_first_name(): void
    {
        $cat = ServiceCategory::factory()->create(['is_active' => true]);
        $worker = $this->professional([$cat]);
        $reviewer = User::factory()->customer()->create(['name' => 'Fatima Al-Khoury']);

        \App\Models\Review::factory()->create([
            'worker_id' => $worker->id,
            'customer_id' => $reviewer->id,
            'rating' => 5,
            'comment' => 'Great work.',
        ]);
        $worker->workerProfile->update(['rating_count' => 1, 'rating_average' => 5]);

        $response = $this->get(route('workers.show', $worker));

        $response->assertOk();
        $response->assertSee('Fatima');
        $response->assertDontSee('Al-Khoury');
    }
}
