<?php

namespace Tests\Feature\Worker;

use App\Http\Requests\StorePortfolioItemRequest;
use App\Http\Requests\UpdatePortfolioItemRequest;
use App\Models\User;
use App\Models\WorkerPortfolioItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Phase 34 Step 2B — worker-side portfolio CRUD + secure image upload.
 * Ownership is controller-scoped (like worker services / availability); every
 * write goes through workerProfile->portfolioItems() and `worker_id` is never
 * taken from the request.
 */
class WorkerPortfolioManagementTest extends TestCase
{
    use InteractsWithWorkers, RefreshDatabase;

    private function image(string $name = 'work.png'): UploadedFile
    {
        return new UploadedFile(base_path('tests/Fixtures/pixel.png'), $name, 'image/png', null, true);
    }

    private function item(User $worker, array $attrs = []): WorkerPortfolioItem
    {
        return WorkerPortfolioItem::factory()->create(['worker_id' => $worker->id, ...$attrs]);
    }

    /* ── read ──────────────────────────────────────────────────────────── */

    public function test_a_worker_sees_only_their_own_portfolio_on_the_index(): void
    {
        $worker = $this->workerUser();
        $this->item($worker, ['title' => 'My Kitchen Rewire']);

        $other = $this->workerUser();
        $this->item($other, ['title' => 'Someone Elses Job']);

        $this->actingAs($worker)->get(route('worker.portfolio.index'))
            ->assertOk()
            ->assertSee('My Kitchen Rewire')
            ->assertDontSee('Someone Elses Job');
    }

    public function test_the_empty_state_points_the_worker_at_the_add_form(): void
    {
        $worker = $this->workerUser();

        $this->actingAs($worker)->get(route('worker.portfolio.index'))
            ->assertOk()
            ->assertSee(__('workers.portfolio_none_title'))
            ->assertSee('href="#add-portfolio-item"', false)
            ->assertSee('id="add-portfolio-item"', false);
    }

    /* ── create + upload ──────────────────────────────────────────────── */

    public function test_a_worker_can_add_a_portfolio_item_with_an_image(): void
    {
        Storage::fake('local');
        $worker = $this->workerUser();

        $this->actingAs($worker)->post(route('worker.portfolio.store'), [
            'image' => $this->image('../../evil.php.png'),
            'title' => 'Bathroom remodel',
            'description' => 'Full retile and new fixtures.',
            'is_visible' => '1',
            'sort_order' => '2',
        ])->assertRedirect();

        $item = $worker->workerProfile->portfolioItems()->sole();
        $this->assertSame($worker->id, $item->worker_id);
        $this->assertSame('Bathroom remodel', $item->title);
        $this->assertTrue($item->is_visible);
        $this->assertSame(2, $item->sort_order);
        $this->assertMatchesRegularExpression('#^worker-portfolio/[A-Za-z0-9]+\.png$#', $item->image_path);
        $this->assertStringNotContainsString('evil', $item->image_path);
        $this->assertStringNotContainsString('..', $item->image_path);
        Storage::disk('local')->assertExists($item->image_path);
    }

    public function test_store_requires_a_real_raster_image(): void
    {
        Storage::fake('local');
        $worker = $this->workerUser();

        $this->actingAs($worker)->from(route('worker.portfolio.index'))
            ->post(route('worker.portfolio.store'), ['is_visible' => '1'])
            ->assertSessionHasErrors('image');

        $this->actingAs($worker)->from(route('worker.portfolio.index'))
            ->post(route('worker.portfolio.store'), [
                'image' => UploadedFile::fake()->create('logo.svg', 8, 'image/svg+xml'),
                'is_visible' => '1',
            ])->assertSessionHasErrors('image');

        $this->actingAs($worker)->from(route('worker.portfolio.index'))
            ->post(route('worker.portfolio.store'), [
                'image' => UploadedFile::fake()->create('payload.php', 8, 'application/x-php'),
                'is_visible' => '1',
            ])->assertSessionHasErrors('image');

        $this->assertSame(0, WorkerPortfolioItem::count());
    }

    public function test_store_rejects_an_image_over_the_five_megabyte_limit(): void
    {
        Storage::fake('local');
        $worker = $this->workerUser();

        $this->actingAs($worker)->from(route('worker.portfolio.index'))
            ->post(route('worker.portfolio.store'), [
                'image' => UploadedFile::fake()->create('huge.jpg', 6000, 'image/jpeg'), // 6 MB
                'is_visible' => '1',
            ])->assertSessionHasErrors('image');

        $this->assertSame(0, WorkerPortfolioItem::count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_store_rejects_a_non_image_masquerading_as_a_png(): void
    {
        Storage::fake('local');
        $worker = $this->workerUser();

        $this->actingAs($worker)->from(route('worker.portfolio.index'))
            ->post(route('worker.portfolio.store'), [
                'image' => UploadedFile::fake()->create('photo.png', 10, 'text/plain'),
                'is_visible' => '1',
            ])->assertSessionHasErrors('image');

        $this->assertSame(0, WorkerPortfolioItem::count());
    }

    public function test_store_requires_the_visibility_flag(): void
    {
        Storage::fake('local');
        $worker = $this->workerUser();

        $this->actingAs($worker)->from(route('worker.portfolio.index'))
            ->post(route('worker.portfolio.store'), ['image' => $this->image()])
            ->assertSessionHasErrors('is_visible');

        $this->assertSame(0, WorkerPortfolioItem::count());
    }

    public function test_the_upload_rules_keep_the_type_size_and_dimension_guards(): void
    {
        // A GD-free lock on the exact rule set, so a future edit to either
        // FormRequest cannot silently drop the raster-only / 5 MB / dimension
        // limits. (UploadedFile::fake()->image() can't run here — no GD.)
        foreach ([StorePortfolioItemRequest::class, UpdatePortfolioItemRequest::class] as $class) {
            $rules = (new $class)->rules()['image'];
            $this->assertContains('mimes:jpg,jpeg,png,webp', $rules, "$class lost the mimes allow-list");
            $this->assertContains('mimetypes:image/jpeg,image/png,image/webp', $rules, "$class lost the mimetypes check");
            $this->assertContains('max:5120', $rules, "$class lost the 5 MB limit");
            $this->assertContains('dimensions:max_width=6000,max_height=6000', $rules, "$class lost the dimension cap");
            $this->assertContains('image', $rules, "$class lost the base image rule");
        }
    }

    public function test_worker_id_in_the_request_body_is_ignored(): void
    {
        Storage::fake('local');
        $worker = $this->workerUser();
        $victim = $this->workerUser();

        $this->actingAs($worker)->post(route('worker.portfolio.store'), [
            'image' => $this->image(),
            'is_visible' => '1',
            'worker_id' => $victim->id,
            'id' => 999,
        ])->assertRedirect();

        $this->assertDatabaseHas('worker_portfolio_items', ['worker_id' => $worker->id]);
        $this->assertDatabaseMissing('worker_portfolio_items', ['worker_id' => $victim->id]);
    }

    /* ── update ───────────────────────────────────────────────────────── */

    public function test_a_worker_can_edit_their_items_text_visibility_and_order(): void
    {
        $worker = $this->workerUser();
        $item = $this->item($worker, ['title' => 'Old', 'is_visible' => true, 'sort_order' => 0]);

        $this->actingAs($worker)->put(route('worker.portfolio.update', $item), [
            'title' => 'New title',
            'description' => 'Updated notes.',
            'is_visible' => '0',
            'sort_order' => '5',
        ])->assertRedirect();

        $item->refresh();
        $this->assertSame('New title', $item->title);
        $this->assertSame('Updated notes.', $item->description);
        $this->assertFalse($item->is_visible);
        $this->assertSame(5, $item->sort_order);
    }

    public function test_replacing_the_image_deletes_the_previous_file(): void
    {
        Storage::fake('local');
        $worker = $this->workerUser();

        $this->actingAs($worker)->post(route('worker.portfolio.store'), [
            'image' => $this->image('first.png'), 'is_visible' => '1',
        ])->assertRedirect();
        $item = $worker->workerProfile->portfolioItems()->sole();
        $first = $item->image_path;

        $this->actingAs($worker)->put(route('worker.portfolio.update', $item), [
            'image' => $this->image('second.png'), 'is_visible' => '1',
        ])->assertRedirect();
        $second = $item->fresh()->image_path;

        $this->assertNotSame($first, $second);
        Storage::disk('local')->assertMissing($first);
        Storage::disk('local')->assertExists($second);
    }

    public function test_the_update_path_rejects_a_bad_replacement_and_keeps_the_original(): void
    {
        Storage::fake('local');
        $worker = $this->workerUser();

        $this->actingAs($worker)->post(route('worker.portfolio.store'), [
            'image' => $this->image('original.png'), 'title' => 'Kept', 'is_visible' => '1',
        ])->assertRedirect();
        $item = $worker->workerProfile->portfolioItems()->sole();
        $original = $item->image_path;

        // SVG replacement — rejected, nothing changes.
        $this->actingAs($worker)->from(route('worker.portfolio.index'))
            ->put(route('worker.portfolio.update', $item), [
                'image' => UploadedFile::fake()->create('sneaky.svg', 8, 'image/svg+xml'),
                'is_visible' => '1',
            ])->assertSessionHasErrors('image');

        // Oversized replacement — also rejected.
        $this->actingAs($worker)->from(route('worker.portfolio.index'))
            ->put(route('worker.portfolio.update', $item), [
                'image' => UploadedFile::fake()->create('huge.jpg', 6000, 'image/jpeg'),
                'is_visible' => '1',
            ])->assertSessionHasErrors('image');

        $this->assertSame($original, $item->fresh()->image_path);
        Storage::disk('local')->assertExists($original);
    }

    public function test_updating_the_text_only_keeps_the_current_image(): void
    {
        Storage::fake('local');
        $worker = $this->workerUser();

        $this->actingAs($worker)->post(route('worker.portfolio.store'), [
            'image' => $this->image('keep.png'), 'is_visible' => '1',
        ])->assertRedirect();
        $item = $worker->workerProfile->portfolioItems()->sole();
        $path = $item->image_path;

        $this->actingAs($worker)->put(route('worker.portfolio.update', $item), [
            'title' => 'Renamed only', 'is_visible' => '0', 'sort_order' => '3',
        ])->assertRedirect();

        $item->refresh();
        $this->assertSame($path, $item->image_path);
        $this->assertSame('Renamed only', $item->title);
        $this->assertFalse($item->is_visible);
        Storage::disk('local')->assertExists($path);
    }

    /* ── delete ───────────────────────────────────────────────────────── */

    public function test_a_worker_can_delete_their_item_and_its_file(): void
    {
        Storage::fake('local');
        $worker = $this->workerUser();

        $this->actingAs($worker)->post(route('worker.portfolio.store'), [
            'image' => $this->image(), 'is_visible' => '1',
        ])->assertRedirect();
        $item = $worker->workerProfile->portfolioItems()->sole();

        $this->actingAs($worker)->delete(route('worker.portfolio.destroy', $item))->assertRedirect();

        $this->assertDatabaseMissing('worker_portfolio_items', ['id' => $item->id]);
        Storage::disk('local')->assertMissing($item->image_path);
    }

    /* ── ownership / IDOR ─────────────────────────────────────────────── */

    public function test_a_worker_cannot_update_another_workers_item(): void
    {
        $owner = $this->workerUser();
        $item = $this->item($owner, ['title' => 'Owner title', 'is_visible' => true]);
        $intruder = $this->workerUser();

        $this->actingAs($intruder)->put(route('worker.portfolio.update', $item), [
            'title' => 'Hijacked', 'is_visible' => '0',
        ])->assertNotFound();

        $item->refresh();
        $this->assertSame('Owner title', $item->title);
        $this->assertTrue($item->is_visible);
    }

    public function test_a_worker_cannot_delete_another_workers_item(): void
    {
        $owner = $this->workerUser();
        $item = $this->item($owner);
        $intruder = $this->workerUser();

        $this->actingAs($intruder)->delete(route('worker.portfolio.destroy', $item))->assertNotFound();

        $this->assertDatabaseHas('worker_portfolio_items', ['id' => $item->id]);
    }

    public function test_a_worker_cannot_stream_another_workers_portfolio_image(): void
    {
        Storage::fake('local');
        $owner = $this->workerUser();
        $this->actingAs($owner)->post(route('worker.portfolio.store'), [
            'image' => $this->image(), 'is_visible' => '1',
        ])->assertRedirect();
        $item = $owner->workerProfile->portfolioItems()->sole();

        $intruder = $this->workerUser();
        $this->actingAs($intruder)->get(route('worker.portfolio.image', $item))->assertNotFound();

        $this->actingAs($owner)->get(route('worker.portfolio.image', $item))->assertOk();
    }

    /* ── access control ──────────────────────────────────────────────── */

    public function test_a_customer_cannot_reach_portfolio_management(): void
    {
        $worker = $this->workerUser();
        $item = $this->item($worker);
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)->get(route('worker.portfolio.index'))->assertForbidden();
        $this->actingAs($customer)->post(route('worker.portfolio.store'))->assertForbidden();
        $this->actingAs($customer)->put(route('worker.portfolio.update', $item))->assertForbidden();
        $this->actingAs($customer)->delete(route('worker.portfolio.destroy', $item))->assertForbidden();
        $this->actingAs($customer)->get(route('worker.portfolio.image', $item))->assertForbidden();
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get(route('worker.portfolio.index'))->assertRedirect(route('login'));
        $this->post(route('worker.portfolio.store'))->assertRedirect(route('login'));
        $this->put(route('worker.portfolio.update', 1))->assertRedirect(route('login'));
        $this->delete(route('worker.portfolio.destroy', 1))->assertRedirect(route('login'));
    }
}
