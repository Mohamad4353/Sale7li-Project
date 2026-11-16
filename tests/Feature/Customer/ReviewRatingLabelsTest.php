<?php

namespace Tests\Feature\Customer;

use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Worker\InteractsWithWorkers;
use Tests\TestCase;

/**
 * P14-17 — the customer review rating select uses accessible localized labels
 * instead of star glyphs, while still submitting the plain numeric value.
 */
class ReviewRatingLabelsTest extends TestCase
{
    use InteractsWithWorkers;
    use RefreshDatabase;

    /** @return array{0: User, 1: ServiceRequest} */
    private function completedJob(): array
    {
        $customer = User::factory()->customer()->create();
        $worker = $this->workerUser();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('completed')->assignedTo($worker)->create([
            'service_category_id' => ServiceCategory::factory()->create()->id,
        ]);

        return [$customer, $request];
    }

    public function test_rating_select_uses_localized_labels_not_star_glyphs(): void
    {
        [$customer, $request] = $this->completedJob();

        $this->actingAs($customer)->get(route('customer.requests.show', $request))
            ->assertOk()
            ->assertSee('5 — '.__('reviews.scale.5'))
            ->assertSee('1 — '.__('reviews.scale.1'))
            ->assertSee('value="5"', false)
            ->assertDontSee('★★★★★');
    }

    public function test_rating_select_still_submits_a_numeric_value(): void
    {
        [$customer, $request] = $this->completedJob();

        $this->actingAs($customer)
            ->post(route('customer.reviews.store', $request), ['rating' => 4, 'comment' => 'Solid work.'])
            ->assertRedirect();

        $this->assertDatabaseHas('reviews', [
            'service_request_id' => $request->id,
            'rating' => 4,
        ]);
    }

    public function test_labels_render_in_arabic(): void
    {
        [$customer, $request] = $this->completedJob();

        $this->actingAs($customer)->withSession(['locale' => 'ar'])
            ->get(route('customer.requests.show', $request))
            ->assertOk()
            ->assertSee(__('reviews.scale.5', [], 'ar'));
    }
}
