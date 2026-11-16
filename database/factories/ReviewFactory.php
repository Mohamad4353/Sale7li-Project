<?php

namespace Database\Factories;

use App\Models\Review;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReviewFactory extends Factory
{
    protected $model = Review::class;

    public function definition(): array
    {
        return [
            'service_request_id' => ServiceRequest::factory()->status('completed'),
            'customer_id' => User::factory()->customer(),
            'worker_id' => User::factory()->worker(),
            'rating' => fake()->numberBetween(3, 5),
            'comment' => fake()->sentence(),
            'photos' => [],
        ];
    }

    public function rating(int $stars): static
    {
        return $this->state(fn () => ['rating' => $stars]);
    }
}
