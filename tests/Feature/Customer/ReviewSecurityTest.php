<?php

namespace Tests\Feature\Customer;

use App\Models\Review;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\WorkerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Audit gap-fill for reviews & ratings: rating-bounds validation, that the
 * server owns every identity column on the row (worker_id / customer_id /
 * service_request_id / id are never taken from the request body), and that the
 * profile aggregate is the true mean/count across several jobs. No production
 * code changes.
 */
class ReviewSecurityTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{customer: User, worker: User, request: ServiceRequest} */
    private function completedJob(): array
    {
        $customer = User::factory()->customer()->create();
        $worker = User::factory()->worker()->create();
        WorkerProfile::factory()->for($worker, 'user')->verified()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('completed')->create([
            'worker_id' => $worker->id,
        ]);

        return compact('customer', 'worker', 'request');
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function badRatings(): array
    {
        return [
            'zero' => [0],
            'above the scale' => [6],
            'negative' => [-3],
            'fractional' => [3.5],
            'non-numeric' => ['great'],
            'missing' => [null],
        ];
    }

    /** @dataProvider badRatings */
    public function test_rating_must_be_an_integer_from_one_to_five(mixed $rating): void
    {
        ['customer' => $customer, 'request' => $request] = $this->completedJob();

        $payload = ['comment' => 'x'];
        if ($rating !== null) {
            $payload['rating'] = $rating;
        }

        $this->actingAs($customer)
            ->from(route('customer.requests.show', $request))
            ->post(route('customer.reviews.store', $request), $payload)
            ->assertSessionHasErrors('rating');

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_identity_columns_come_from_the_server_not_the_request_body(): void
    {
        ['customer' => $customer, 'worker' => $worker, 'request' => $request] = $this->completedJob();

        $otherCustomer = User::factory()->customer()->create();
        $otherWorker = User::factory()->worker()->create();
        $foreignRequest = ServiceRequest::factory()->status('completed')->create([
            'worker_id' => $otherWorker->id,
        ]);

        $this->actingAs($customer)
            ->post(route('customer.reviews.store', $request), [
                'rating' => 5,
                'comment' => 'Great.',
                // every one of these must be ignored
                'customer_id' => $otherCustomer->id,
                'worker_id' => $otherWorker->id,
                'service_request_id' => $foreignRequest->id,
                'id' => 999999,
            ])
            ->assertRedirect();

        $review = Review::sole();
        $this->assertSame($customer->id, $review->customer_id);
        $this->assertSame($worker->id, $review->worker_id);
        $this->assertSame($request->id, $review->service_request_id);
        $this->assertNotSame(999999, $review->id);

        // The foreign request and the other worker's reputation are untouched.
        $this->assertSame(0, Review::where('service_request_id', $foreignRequest->id)->count());
        $this->assertSame(0, $otherWorker->workerProfile()->value('rating_count') ?? 0);
    }

    public function test_a_customer_cannot_review_another_customers_completed_job(): void
    {
        ['request' => $request] = $this->completedJob();
        $stranger = User::factory()->customer()->create();

        $this->actingAs($stranger)
            ->post(route('customer.reviews.store', $request), ['rating' => 1])
            ->assertForbidden();

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_profile_rating_average_and_count_are_the_true_mean_over_all_reviewed_jobs(): void
    {
        $customer = User::factory()->customer()->create();
        $worker = User::factory()->worker()->create();
        $profile = WorkerProfile::factory()->for($worker, 'user')->verified()->create();

        foreach ([5, 2, 4] as $rating) {
            $request = ServiceRequest::factory()->for($customer, 'customer')->status('completed')->create([
                'worker_id' => $worker->id,
            ]);
            $this->actingAs($customer)
                ->post(route('customer.reviews.store', $request), ['rating' => $rating])
                ->assertRedirect();
        }

        $profile->refresh();
        $this->assertSame(3, $profile->rating_count);
        // (5 + 2 + 4) / 3 = 3.67 (round, 2 dp)
        $this->assertEqualsWithDelta(3.67, (float) $profile->rating_average, 0.001);
        $this->assertSame(3, Review::where('worker_id', $worker->id)->count());
    }
}
