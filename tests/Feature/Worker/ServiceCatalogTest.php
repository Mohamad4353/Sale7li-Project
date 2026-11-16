<?php

namespace Tests\Feature\Worker;

use App\Models\ServiceCategory;
use App\Models\WorkerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceCatalogTest extends TestCase
{
    use InteractsWithWorkers, RefreshDatabase;

    public function test_a_worker_can_add_a_service_they_offer(): void
    {
        $worker = $this->workerUser();
        $category = ServiceCategory::factory()->create(['is_active' => true]);

        $this->actingAs($worker)->put(route('worker.services.update'), [
            'services' => [$category->id => ['enabled' => '1', 'price_from' => '55']],
        ])->assertRedirect();

        $this->assertDatabaseHas('worker_services', [
            'worker_id' => $worker->id,
            'service_category_id' => $category->id,
            'is_active' => true,
            'price_from' => 55,
        ]);
    }

    public function test_a_worker_can_disable_a_service(): void
    {
        $worker = $this->workerUser();
        $category = ServiceCategory::factory()->create(['is_active' => true]);
        $this->activeService($worker, $category);

        $this->actingAs($worker)->put(route('worker.services.update'), [
            'services' => [$category->id => ['enabled' => '0']],
        ])->assertRedirect();

        $this->assertDatabaseHas('worker_services', [
            'worker_id' => $worker->id,
            'service_category_id' => $category->id,
            'is_active' => false,
        ]);
    }

    public function test_a_worker_can_update_the_starting_price_and_clear_it(): void
    {
        $worker = $this->workerUser();
        $category = ServiceCategory::factory()->create(['is_active' => true]);
        $this->activeService($worker, $category, ['price_from' => 40]);

        $this->actingAs($worker)->put(route('worker.services.update'), [
            'services' => [$category->id => ['enabled' => '1', 'price_from' => '90']],
        ])->assertRedirect();
        $this->assertDatabaseHas('worker_services', ['worker_id' => $worker->id, 'service_category_id' => $category->id, 'price_from' => 90]);

        $this->actingAs($worker)->put(route('worker.services.update'), [
            'services' => [$category->id => ['enabled' => '1', 'price_from' => '']],
        ])->assertRedirect();
        $this->assertDatabaseHas('worker_services', ['worker_id' => $worker->id, 'service_category_id' => $category->id, 'price_from' => null]);
    }

    public function test_a_negative_or_oversized_price_is_rejected(): void
    {
        $worker = $this->workerUser();
        $category = ServiceCategory::factory()->create(['is_active' => true]);

        $this->actingAs($worker)->from(route('worker.services.edit'))->put(route('worker.services.update'), [
            'services' => [$category->id => ['enabled' => '1', 'price_from' => '-5']],
        ])->assertSessionHasErrors('services.'.$category->id.'.price_from');

        $this->actingAs($worker)->from(route('worker.services.edit'))->put(route('worker.services.update'), [
            'services' => [$category->id => ['enabled' => '1', 'price_from' => '250000']],
        ])->assertSessionHasErrors('services.'.$category->id.'.price_from');

        $this->assertDatabaseCount('worker_services', 0);
    }

    public function test_an_inactive_or_nonexistent_category_cannot_be_added(): void
    {
        $worker = $this->workerUser();
        $inactive = ServiceCategory::factory()->create(['is_active' => false]);

        $this->actingAs($worker)->put(route('worker.services.update'), [
            'services' => [
                $inactive->id => ['enabled' => '1', 'price_from' => '50'],
                999999 => ['enabled' => '1', 'price_from' => '50'],
            ],
        ])->assertRedirect();

        $this->assertDatabaseCount('worker_services', 0);
    }

    public function test_a_worker_cannot_modify_another_workers_services(): void
    {
        $owner = $this->workerUser();
        $intruder = $this->workerUser();
        $category = ServiceCategory::factory()->create(['is_active' => true]);
        $service = $this->activeService($owner, $category, ['price_from' => 40]);

        $this->actingAs($intruder)->put(route('worker.services.update'), [
            'services' => [$category->id => ['enabled' => '0']],
        ])->assertRedirect();

        // The owner's row is untouched; the intruder only ever wrote their own.
        $this->assertDatabaseHas('worker_services', ['id' => $service->id, 'is_active' => true, 'price_from' => 40]);
        $this->assertDatabaseMissing('worker_services', ['worker_id' => $intruder->id]);
    }

    public function test_a_customer_cannot_reach_the_service_catalog(): void
    {
        $customer = \App\Models\User::factory()->customer()->create();

        $this->actingAs($customer)->get(route('worker.services.edit'))->assertForbidden();
        $this->actingAs($customer)->put(route('worker.services.update'), ['services' => []])->assertForbidden();
        $this->assertSame(0, WorkerService::count());
    }
}
