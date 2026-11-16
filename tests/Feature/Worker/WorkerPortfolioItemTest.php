<?php

namespace Tests\Feature\Worker;

use App\Models\WorkerPortfolioItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Phase 34 Step 2A — data-layer contract for the worker portfolio table.
 * No controllers, routes, uploads or UI yet: this only pins the schema,
 * ownership wiring and cascade behaviour the later steps will build on.
 */
class WorkerPortfolioItemTest extends TestCase
{
    use InteractsWithWorkers, RefreshDatabase;

    public function test_a_portfolio_item_belongs_to_exactly_one_worker_and_profile(): void
    {
        $worker = $this->workerUser();
        $item = WorkerPortfolioItem::factory()->create(['worker_id' => $worker->id]);

        $this->assertTrue($item->worker->is($worker));
        $this->assertTrue($item->workerProfile->is($worker->workerProfile));
    }

    public function test_a_worker_profile_has_many_portfolio_items_scoped_to_itself(): void
    {
        $mine = $this->workerUser();
        $other = $this->workerUser();

        WorkerPortfolioItem::factory()->count(3)->create(['worker_id' => $mine->id]);
        WorkerPortfolioItem::factory()->count(2)->create(['worker_id' => $other->id]);

        $items = $mine->workerProfile->portfolioItems;

        $this->assertCount(3, $items);
        $this->assertTrue($items->every(fn (WorkerPortfolioItem $i) => $i->worker_id === $mine->id));
    }

    public function test_deleting_the_worker_cascades_to_their_portfolio_items(): void
    {
        $worker = $this->workerUser();
        $item = WorkerPortfolioItem::factory()->create(['worker_id' => $worker->id]);

        $worker->delete();

        $this->assertDatabaseMissing('worker_portfolio_items', ['id' => $item->id]);
    }

    public function test_visibility_and_order_have_sane_defaults_and_casts(): void
    {
        $worker = $this->workerUser();

        $item = WorkerPortfolioItem::create([
            'worker_id' => $worker->id,
            'image_path' => 'worker-portfolio/example.jpg',
        ])->fresh();

        $this->assertSame(0, $item->sort_order);
        $this->assertTrue($item->is_visible);
        $this->assertIsBool($item->is_visible);
        $this->assertNull($item->title);
        $this->assertNull($item->description);
    }

    public function test_hidden_items_can_be_filtered_out_for_a_public_gallery(): void
    {
        $worker = $this->workerUser();
        WorkerPortfolioItem::factory()->count(2)->create(['worker_id' => $worker->id]);
        WorkerPortfolioItem::factory()->hidden()->create(['worker_id' => $worker->id]);

        $visible = $worker->workerProfile->portfolioItems()->where('is_visible', true)->get();

        $this->assertCount(2, $visible);
    }

    public function test_the_table_matches_the_intended_contract(): void
    {
        $this->assertTrue(Schema::hasColumns('worker_portfolio_items', [
            'id', 'worker_id', 'image_path', 'title', 'description',
            'sort_order', 'is_visible', 'created_at', 'updated_at',
        ]));

        // Deletion is real everywhere else in SALE7LI — no soft deletes here either.
        $this->assertFalse(Schema::hasColumn('worker_portfolio_items', 'deleted_at'));
    }
}
