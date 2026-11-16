<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WorkerProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkerProfileFactory extends Factory
{
    protected $model = WorkerProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->worker(),
            'bio' => fake()->sentence(),
            'bio_ar' => 'نبذة مهنية.',
            'experience_years' => fake()->numberBetween(1, 20),
            'verification_status' => 'pending',
            'rating_average' => 0,
            'rating_count' => 0,
            'completed_jobs' => 0,
            'completion_rate' => 100,
            'cancellation_rate' => 0,
            'service_radius_km' => 15,
        ];
    }

    public function verified(): static
    {
        return $this->state(fn () => ['verification_status' => 'verified']);
    }
}
