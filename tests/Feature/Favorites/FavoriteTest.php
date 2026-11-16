<?php

namespace Tests\Feature\Favorites;

use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\WorkerProfile;
use App\Models\WorkerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature 6 — favorites. Reuses the existing `favorites` pivot and
 * User::favoriteWorkers(); adds a controller, routes and UI only.
 */
class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    private function visibleWorker(array $attrs = []): User
    {
        $worker = User::factory()->worker()->create($attrs);
        WorkerProfile::factory()->for($worker, 'user')->create(['verification_status' => 'verified']);
        WorkerService::create([
            'worker_id' => $worker->id,
            'service_category_id' => ServiceCategory::factory()->create()->id,
            'price_from' => 40,
            'is_active' => true,
        ]);

        return $worker;
    }

    public function test_customer_can_favorite_then_unfavorite_a_worker(): void
    {
        $customer = User::factory()->customer()->create();
        $worker = $this->visibleWorker();

        $this->actingAs($customer)->postJson(route('customer.favorites.toggle', $worker))
            ->assertOk()->assertJson(['favorited' => true, 'count' => 1]);
        $this->assertDatabaseHas('favorites', ['customer_id' => $customer->id, 'worker_id' => $worker->id]);

        $this->actingAs($customer)->postJson(route('customer.favorites.toggle', $worker))
            ->assertOk()->assertJson(['favorited' => false, 'count' => 0]);
        $this->assertDatabaseMissing('favorites', ['customer_id' => $customer->id, 'worker_id' => $worker->id]);
    }

    public function test_a_plain_form_post_redirects_back_with_a_flash(): void
    {
        $customer = User::factory()->customer()->create();
        $worker = $this->visibleWorker();

        $this->actingAs($customer)->from(route('workers.show', $worker))
            ->post(route('customer.favorites.toggle', $worker))
            ->assertRedirect(route('workers.show', $worker))
            ->assertSessionHas('success');
    }

    public function test_index_lists_only_the_customers_own_active_saved_workers(): void
    {
        $customer = User::factory()->customer()->create();
        $kept = $this->visibleWorker(['name' => 'Kept Pro']);
        $suspended = $this->visibleWorker(['name' => 'Suspended Pro']);
        $other = $this->visibleWorker(['name' => 'Someone Elses Pro']);

        $customer->favoriteWorkers()->attach([$kept->id, $suspended->id]);
        $suspended->update(['status' => 'suspended']);
        User::factory()->customer()->create()->favoriteWorkers()->attach($other->id);

        $this->actingAs($customer)->get(route('customer.favorites.index'))
            ->assertOk()
            ->assertSee('Kept Pro')
            ->assertDontSee('Suspended Pro')
            ->assertDontSee('Someone Elses Pro');
    }

    public function test_empty_state_when_nothing_is_saved(): void
    {
        $this->actingAs(User::factory()->customer()->create())
            ->get(route('customer.favorites.index'))
            ->assertOk()
            ->assertSee(__('favorites.empty_title'));
    }

    public function test_guests_and_non_customers_cannot_toggle_favorites(): void
    {
        $worker = $this->visibleWorker();

        $this->post(route('customer.favorites.toggle', $worker))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->worker()->create())->post(route('customer.favorites.toggle', $worker))->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->post(route('customer.favorites.toggle', $worker))->assertForbidden();

        $this->assertDatabaseCount('favorites', 0);
    }

    public function test_favouriting_a_non_worker_is_rejected(): void
    {
        $customer = User::factory()->customer()->create();
        $target = User::factory()->customer()->create();

        $this->actingAs($customer)->postJson(route('customer.favorites.toggle', $target))->assertNotFound();
        $this->assertDatabaseCount('favorites', 0);
    }

    public function test_toggling_is_scoped_to_the_acting_customer(): void
    {
        $a = User::factory()->customer()->create();
        $b = User::factory()->customer()->create();
        $worker = $this->visibleWorker();

        $a->favoriteWorkers()->attach($worker->id);
        $this->actingAs($b)->postJson(route('customer.favorites.toggle', $worker))->assertJson(['favorited' => true]);

        // B favouriting did not remove A's favorite.
        $this->assertDatabaseHas('favorites', ['customer_id' => $a->id, 'worker_id' => $worker->id]);
        $this->assertDatabaseHas('favorites', ['customer_id' => $b->id, 'worker_id' => $worker->id]);
    }

    public function test_the_save_button_shows_on_a_worker_profile_for_customers_only(): void
    {
        $worker = $this->visibleWorker();

        $this->actingAs(User::factory()->customer()->create())
            ->get(route('workers.show', $worker))
            ->assertOk()
            ->assertSee(route('customer.favorites.toggle', $worker), false);

        $this->app['auth']->forgetGuards();
        $this->get(route('workers.show', $worker))
            ->assertOk()
            ->assertDontSee(route('customer.favorites.toggle', $worker), false);
    }

    public function test_favorites_index_renders_in_arabic(): void
    {
        $this->actingAs(User::factory()->customer()->create())
            ->withSession(['locale' => 'ar'])
            ->get(route('customer.favorites.index'))
            ->assertOk()
            ->assertSee(__('favorites.title', [], 'ar'))
            ->assertDontSee(__('favorites.title', [], 'en'));
    }
}
