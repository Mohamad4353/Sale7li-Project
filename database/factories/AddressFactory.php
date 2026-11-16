<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AddressFactory extends Factory
{
    protected $model = Address::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->customer(),
            'title' => 'Home',
            'street' => fake()->streetAddress(),
            'city' => 'Beirut',
            'area' => 'Hamra',
            'postal_code' => (string) fake()->numberBetween(1000, 9999),
            'instructions' => 'Call before arriving.',
            'is_default' => true,
        ];
    }
}
