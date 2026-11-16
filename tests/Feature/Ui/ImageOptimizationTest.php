<?php

namespace Tests\Feature\Ui;

use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\WorkerProfile;
use App\Models\WorkerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * P14-05 — the runtime has neither GD nor Imagick, so no thumbnail generation
 * is added. The safe fallback: lazy/async decoding and explicit dimensions on
 * thumbnails and avatars. The private streaming path is unchanged.
 */
class ImageOptimizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_marketplace_worker_avatar_image_is_lazy_and_dimensioned(): void
    {
        $worker = User::factory()->worker()->create(['name' => 'Photo Pro']);
        WorkerProfile::factory()->for($worker, 'user')->create([
            'verification_status' => 'verified',
            'photo' => 'worker-photos/example.png',
        ]);
        WorkerService::create([
            'worker_id' => $worker->id,
            'service_category_id' => ServiceCategory::factory()->create()->id,
            'price_from' => 40, 'is_active' => true,
        ]);

        $html = $this->get(route('workers.index'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/<img[^>]+src="[^"]*'.preg_quote(route('workers.photo', $worker), '/').'[^"]*"[^>]*>/',
            $html
        );
        // the avatar <img> carries the perf hints
        $this->assertMatchesRegularExpression('/<img[^>]+loading="lazy"[^>]+decoding="async"[^>]*>/', $html);
        $this->assertStringContainsString('width="44"', $html);
    }

    public function test_request_problem_thumbnail_is_lazy_and_dimensioned(): void
    {
        $customer = User::factory()->customer()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->create([
            'service_category_id' => ServiceCategory::factory()->create()->id,
            'problem_images' => ['problem/one.jpg'],
        ]);

        $this->actingAs($customer)->get(route('customer.requests.show', $request))->assertOk()
            ->assertSee('loading="lazy"', false)
            ->assertSee('decoding="async"', false)
            ->assertSee('width="80"', false)
            ->assertSee('height="80"', false);
    }

    public function test_private_attachment_still_streams_for_the_owner_and_404s_for_others(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('problem/pixel.png', 'binary-bytes');

        $customer = User::factory()->customer()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->create([
            'service_category_id' => ServiceCategory::factory()->create()->id,
            'problem_images' => ['problem/pixel.png'],
        ]);

        // Owner — original no-parameter URL still works.
        $this->actingAs($customer)
            ->get(route('attachments.request', [$request, 'problem', 0]))
            ->assertOk();

        // An unknown query parameter is ignored, not an error — still the original.
        $this->actingAs($customer)
            ->get(route('attachments.request', [$request, 'problem', 0]).'?size=160')
            ->assertOk();

        // A stranger cannot read it.
        $this->actingAs(User::factory()->customer()->create())
            ->get(route('attachments.request', [$request, 'problem', 0]))
            ->assertForbidden();
    }
}
