<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '+961 71 '.fake()->numberBetween(100, 999).' '.fake()->numberBetween(100, 999),
            'password' => 'Password@123',
            'role' => 'customer',
            'status' => 'active',
            'locale' => 'en',
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ];
    }

    public function customer(): static
    {
        return $this->state(fn () => ['role' => 'customer']);
    }

    public function worker(): static
    {
        return $this->state(fn () => ['role' => 'worker']);
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => 'admin']);
    }

    public function suspended(): static
    {
        return $this->state(fn () => ['status' => 'suspended']);
    }
}
