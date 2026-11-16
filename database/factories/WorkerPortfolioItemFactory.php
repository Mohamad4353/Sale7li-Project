<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WorkerPortfolioItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkerPortfolioItemFactory extends Factory
{
    protected $model = WorkerPortfolioItem::class;

    public function definition(): array
    {
        return [
            'worker_id' => User::factory()->worker(),
            'image_path' => 'worker-portfolio/'.fake()->uuid().'.jpg',
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'sort_order' => 0,
            'is_visible' => true,
        ];
    }

    public function hidden(): static
    {
        return $this->state(fn () => ['is_visible' => false]);
    }
}
