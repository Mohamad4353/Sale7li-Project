<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ServiceRequestFactory extends Factory
{
    protected $model = ServiceRequest::class;

    public function definition(): array
    {
        return [
            'reference' => 'SL-'.strtoupper(Str::random(8)),
            'customer_id' => User::factory()->customer(),
            'worker_id' => null,
            'service_category_id' => ServiceCategory::factory(),
            'address_id' => Address::factory(),
            'problem_description' => fake()->paragraph(),
            'problem_images' => [],
            'preferred_date' => now()->addDay()->toDateString(),
            'preferred_time' => '10:00',
            'is_emergency' => false,
            'status' => 'pending',
            'payment_status' => 'pending',
        ];
    }

    public function status(string $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }

    public function assignedTo(User $worker): static
    {
        return $this->state(fn () => ['worker_id' => $worker->id]);
    }
}
