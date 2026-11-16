<?php

namespace Tests\Feature\Worker;

use App\Models\User;
use App\Models\WorkerPortfolioItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Phase 34 Step 2C — the public work gallery on GET /professionals/{worker}.
 * Only `is_visible` items on a marketplace-visible professional are shown, and
 * their images stream from the private disk under the same rules as the
 * profile photo.
 */
class WorkerProfilePortfolioTest extends TestCase
{
    use InteractsWithWorkers, RefreshDatabase;

    private function visibleProfessional(string $name = 'Gallery Gina'): User
    {
        $worker = $this->workerUser(['name' => $name]);
        $this->activeService($worker);

        return $worker;
    }

    private function item(User $worker, array $attrs = []): WorkerPortfolioItem
    {
        return WorkerPortfolioItem::factory()->create(['worker_id' => $worker->id, ...$attrs]);
    }

    /* ── rendering ─────────────────────────────────────────────────────── */

    public function test_visible_items_appear_in_the_gallery_on_the_public_profile(): void
    {
        $worker = $this->visibleProfessional();
        $a = $this->item($worker, ['title' => 'Marble bathroom', 'is_visible' => true]);
        $b = $this->item($worker, ['title' => 'Garden lighting', 'is_visible' => true]);

        $this->get(route('workers.show', $worker))
            ->assertOk()
            ->assertSee(__('workers.portfolio_gallery_title'))
            ->assertSee('Marble bathroom')
            ->assertSee('Garden lighting')
            ->assertSee(route('workers.portfolio.image', [$worker, $a]), false)
            ->assertSee(route('workers.portfolio.image', [$worker, $b]), false);
    }

    public function test_the_public_gallery_renders_item_titles_and_descriptions(): void
    {
        $worker = $this->visibleProfessional();
        $this->item($worker, [
            'title' => 'Full kitchen rewire',
            'description' => 'New consumer unit, 12 circuits, all tested and certified.',
            'is_visible' => true,
        ]);

        $this->get(route('workers.show', $worker))
            ->assertOk()
            ->assertSee('Full kitchen rewire')
            ->assertSee('New consumer unit, 12 circuits, all tested and certified.');
    }

    public function test_hidden_items_are_never_shown_on_the_public_profile(): void
    {
        $worker = $this->visibleProfessional();
        $this->item($worker, ['title' => 'Shown job', 'is_visible' => true]);
        $hidden = $this->item($worker, ['title' => 'Secret draft', 'is_visible' => false]);

        $html = $this->get(route('workers.show', $worker))->assertOk()->getContent();

        $this->assertStringNotContainsString('Secret draft', $html);
        $this->assertStringNotContainsString(route('workers.portfolio.image', [$worker, $hidden]), $html);
    }

    public function test_the_gallery_section_is_absent_when_nothing_is_visible(): void
    {
        $worker = $this->visibleProfessional();
        $this->item($worker, ['is_visible' => false]);

        $this->get(route('workers.show', $worker))
            ->assertOk()
            ->assertDontSee(__('workers.portfolio_gallery_title'));
    }

    public function test_the_gallery_shows_a_count_of_visible_items_only(): void
    {
        $worker = $this->visibleProfessional();
        WorkerPortfolioItem::factory()->count(3)->create(['worker_id' => $worker->id, 'is_visible' => true]);
        WorkerPortfolioItem::factory()->count(2)->create(['worker_id' => $worker->id, 'is_visible' => false]);

        $this->get(route('workers.show', $worker))
            ->assertOk()
            ->assertSee(trans_choice('workers.portfolio_gallery_count', 3, ['count' => 3]))
            ->assertDontSee(trans_choice('workers.portfolio_gallery_count', 5, ['count' => 5]));
    }

    public function test_the_count_reads_naturally_for_a_single_item(): void
    {
        $worker = $this->visibleProfessional();
        $this->item($worker, ['is_visible' => true]);

        $this->get(route('workers.show', $worker))
            ->assertOk()
            ->assertSee(trans_choice('workers.portfolio_gallery_count', 1, ['count' => 1]));
    }

    public function test_gallery_image_urls_carry_a_cache_busting_version(): void
    {
        $worker = $this->visibleProfessional();
        $item = $this->item($worker, ['is_visible' => true]);

        $this->get(route('workers.show', $worker))
            ->assertOk()
            ->assertSee(route('workers.portfolio.image', [$worker, $item]).'?v='.$item->updated_at->timestamp, false);
    }

    public function test_items_render_in_the_professionals_sort_order(): void
    {
        $worker = $this->visibleProfessional();
        $this->item($worker, ['title' => 'Third', 'sort_order' => 2]);
        $this->item($worker, ['title' => 'First', 'sort_order' => 0]);
        $this->item($worker, ['title' => 'Second', 'sort_order' => 1]);

        $html = $this->get(route('workers.show', $worker))->assertOk()->getContent();

        $this->assertLessThan(strpos($html, 'Second'), strpos($html, 'First'));
        $this->assertLessThan(strpos($html, 'Third'), strpos($html, 'Second'));
    }

    public function test_the_gallery_renders_in_arabic(): void
    {
        $worker = $this->visibleProfessional();
        WorkerPortfolioItem::factory()->count(2)->create(['worker_id' => $worker->id, 'title' => 'Tiling job']);

        $response = $this->withSession(['locale' => 'ar'])->get(route('workers.show', $worker));

        $response->assertOk();
        $response->assertSee('<html lang="ar" dir="rtl">', false);
        $response->assertSee(__('workers.portfolio_gallery_title', [], 'ar'));
        $response->assertSee(trans_choice('workers.portfolio_gallery_count', 2, ['count' => 2], 'ar'));
        $response->assertDontSee('workers.portfolio_gallery_title');
        $response->assertDontSee('workers.portfolio_gallery_count');
    }

    /* ── image streaming / authorization ──────────────────────────────── */

    public function test_a_visible_items_image_streams_to_a_guest(): void
    {
        Storage::fake('local');
        $worker = $this->visibleProfessional();
        $path = Storage::disk('local')->putFileAs('worker-portfolio', $this->realImage(), 'a.png');
        $item = $this->item($worker, ['is_visible' => true, 'image_path' => $path]);

        $this->get(route('workers.portfolio.image', [$worker, $item]))->assertOk();
    }

    public function test_a_hidden_items_image_is_private_to_its_owner(): void
    {
        Storage::fake('local');
        $worker = $this->visibleProfessional();
        $path = Storage::disk('local')->putFileAs('worker-portfolio', $this->realImage(), 'h.png');
        $item = $this->item($worker, ['is_visible' => false, 'image_path' => $path]);

        $this->get(route('workers.portfolio.image', [$worker, $item]))->assertNotFound();
        $this->actingAs(User::factory()->customer()->create())
            ->get(route('workers.portfolio.image', [$worker, $item]))->assertNotFound();

        $this->actingAs($worker)->get(route('workers.portfolio.image', [$worker, $item]))->assertOk();
    }

    public function test_a_non_marketplace_visible_professionals_images_are_not_public(): void
    {
        Storage::fake('local');
        $worker = $this->workerUser(verification: 'pending'); // not verified → not visible
        $this->activeService($worker);
        $path = Storage::disk('local')->putFileAs('worker-portfolio', $this->realImage(), 'p.png');
        $item = $this->item($worker, ['is_visible' => true, 'image_path' => $path]);

        $this->get(route('workers.portfolio.image', [$worker, $item]))->assertNotFound();
        $this->actingAs($worker)->get(route('workers.portfolio.image', [$worker, $item]))->assertOk();
    }

    public function test_the_image_route_scopes_the_item_to_the_worker_in_the_path(): void
    {
        Storage::fake('local');
        $a = $this->visibleProfessional('Worker A');
        $b = $this->visibleProfessional('Worker B');
        $path = Storage::disk('local')->putFileAs('worker-portfolio', $this->realImage(), 'b.png');
        $itemOfB = $this->item($b, ['is_visible' => true, 'image_path' => $path]);

        $this->get(route('workers.portfolio.image', [$a, $itemOfB]))->assertNotFound();
        $this->get(route('workers.portfolio.image', [$b, $itemOfB]))->assertOk();
    }

    public function test_a_missing_image_file_is_a_404_not_a_500(): void
    {
        Storage::fake('local');
        $worker = $this->visibleProfessional();
        $item = $this->item($worker, ['is_visible' => true, 'image_path' => 'worker-portfolio/gone.png']);

        $this->get(route('workers.portfolio.image', [$worker, $item]))->assertNotFound();
    }

    private function realImage(): UploadedFile
    {
        return new UploadedFile(base_path('tests/Fixtures/pixel.png'), 'pixel.png', 'image/png', null, true);
    }
}
