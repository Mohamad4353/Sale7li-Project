<?php

namespace Database\Factories;

use App\Models\Complaint;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ComplaintFactory extends Factory
{
    protected $model = Complaint::class;

    public function definition(): array
    {
        return [
            'service_request_id' => ServiceRequest::factory(),
            'reporter_id' => User::factory()->customer(),
            'category' => fake()->randomElement(['work_quality', 'no_show', 'damage', 'billing', 'behavior', 'other']),
            'priority' => 'medium',
            'status' => 'open',
            'description' => fake()->paragraph(),
            'internal_notes' => null,
        ];
    }
}
