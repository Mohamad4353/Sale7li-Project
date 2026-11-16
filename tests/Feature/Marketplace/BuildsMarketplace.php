<?php

namespace Tests\Feature\Marketplace;

use App\Models\Review;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\WorkerProfile;
use App\Models\WorkerService;

trait BuildsMarketplace
{
    /**
     * A verified, active professional offering the given categories.
     *
     * @param  array<int, ServiceCategory>  $categories
     */
    protected function professional(array $categories = [], array $profile = [], ?string $name = null): User
    {
        $worker = User::factory()->worker()->create($name ? ['name' => $name] : []);

        WorkerProfile::factory()->for($worker, 'user')->verified()->create($profile);

        foreach ($categories as $category) {
            WorkerService::create([
                'worker_id' => $worker->id,
                'service_category_id' => $category->id,
                'price_from' => 45,
                'is_active' => true,
            ]);
        }

        return $worker;
    }

    protected function reviewsFor(User $worker, int $count, int $stars): void
    {
        Review::factory()->count($count)->create([
            'worker_id' => $worker->id,
            'rating' => $stars,
        ]);

        // Discovery reads the denormalised counters that ReviewController keeps.
        $worker->workerProfile->update([
            'rating_count' => $worker->reviewsReceived()->count(),
            'rating_average' => round((float) $worker->reviewsReceived()->avg('rating'), 2),
        ]);
    }
}
