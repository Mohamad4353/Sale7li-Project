<?php

namespace Database\Factories;

use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\WorkerService;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkerServiceFactory extends Factory
{
    protected $model = WorkerService::class;

    public function definition(): array
    {
        return [
            'worker_id' => User::factory()->worker(),
            'service_category_id' => ServiceCategory::factory(),
            'price_from' => fake()->numberBetween(20, 80),
            'is_active' => true,
        ];
    }
}
