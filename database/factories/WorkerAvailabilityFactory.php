<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WorkerAvailability;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkerAvailabilityFactory extends Factory
{
    protected $model = WorkerAvailability::class;

    public function definition(): array
    {
        return [
            'worker_id' => User::factory()->worker(),
            'day_of_week' => fake()->numberBetween(0, 6),
            'start_time' => '09:00',
            'end_time' => '17:00',
            'is_available' => true,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn () => ['is_available' => false, 'start_time' => null, 'end_time' => null]);
    }
}
