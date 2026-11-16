<?php

namespace Tests\Feature\Worker;

use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceVisibilityTest extends TestCase
{
    use InteractsWithWorkers, RefreshDatabase;

    public function test_a_verified_active_worker_with_an_active_service_is_discoverable(): void
    {
        $worker = $this->workerUser(['name' => 'Ready Rana']);
        $this->activeService($worker);

        $this->get(route('workers.index'))->assertOk()->assertSee('Ready Rana');
        $this->get(route('workers.show', $worker))->assertOk();
    }

    public function test_a_verified_worker_with_no_active_service_is_hidden(): void
    {
        $noService = $this->workerUser(['name' => 'Idle Ivan']);

        $category = ServiceCategory::factory()->create(['is_active' => true]);
        $disabledOnly = $this->workerUser(['name' => 'Disabled Dana']);
        $this->activeService($disabledOnly, $category, ['is_active' => false]);

        $response = $this->get(route('workers.index'))->assertOk();
        $response->assertDontSee('Idle Ivan');
        $response->assertDontSee('Disabled Dana');

        $this->get(route('workers.show', $noService))->assertNotFound();
        $this->get(route('workers.show', $disabledOnly))->assertNotFound();
    }

    public function test_a_suspended_worker_is_hidden_even_with_an_active_service(): void
    {
        $worker = $this->workerUser(['name' => 'Suspended Sami', 'status' => 'suspended']);
        $this->activeService($worker);

        $this->get(route('workers.index'))->assertOk()->assertDontSee('Suspended Sami');
        $this->get(route('workers.show', $worker))->assertNotFound();
    }

    public function test_an_unverified_worker_is_hidden_even_with_an_active_service(): void
    {
        $worker = $this->workerUser(['name' => 'Pending Priya'], verification: 'pending');
        $this->activeService($worker);

        $this->get(route('workers.index'))->assertOk()->assertDontSee('Pending Priya');
        $this->get(route('workers.show', $worker))->assertNotFound();
    }

    public function test_a_customer_account_is_not_a_professional(): void
    {
        $customer = User::factory()->customer()->create();

        $this->get(route('workers.show', $customer))->assertNotFound();
    }

    public function test_making_a_service_active_brings_a_verified_worker_into_discovery(): void
    {
        $worker = $this->workerUser(['name' => 'Late Bloomer Lina']);
        $category = ServiceCategory::factory()->create(['is_active' => true]);

        $this->get(route('workers.show', $worker))->assertNotFound();

        $this->actingAs($worker)->put(route('worker.services.update'), [
            'services' => [$category->id => ['enabled' => '1']],
        ])->assertRedirect();

        $this->get(route('workers.show', $worker))->assertOk();
        $this->get(route('workers.index'))->assertOk()->assertSee('Late Bloomer Lina');
    }

    /**
     * The homepage "featured professionals" section must apply the same
     * visibility bar as the marketplace — otherwise a suspended or
     * not-marketplace-ready worker is featured while workers.show 404s.
     */
    public function test_the_homepage_features_only_marketplace_visible_professionals(): void
    {
        $ready = $this->workerUser(['name' => 'Featured Fadi']);
        $this->activeService($ready);
        $ready->workerProfile->update(['rating_average' => 4.9, 'rating_count' => 10]);

        $suspended = $this->workerUser(['name' => 'Suspended Sami', 'status' => 'suspended']);
        $this->activeService($suspended);
        $suspended->workerProfile->update(['rating_average' => 5, 'rating_count' => 20]);

        $serviceless = $this->workerUser(['name' => 'Serviceless Sara']);
        $serviceless->workerProfile->update(['rating_average' => 5, 'rating_count' => 20]);

        $unverified = $this->workerUser(['name' => 'Pending Priya'], verification: 'pending');
        $this->activeService($unverified);
        $unverified->workerProfile->update(['rating_average' => 5, 'rating_count' => 20]);

        $response = $this->get('/')->assertOk();
        $response->assertSee('Featured Fadi');
        $response->assertDontSee('Suspended Sami');
        $response->assertDontSee('Serviceless Sara');
        $response->assertDontSee('Pending Priya');
    }
}
