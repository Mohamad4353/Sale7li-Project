<?php

namespace Database\Factories;

use App\Models\Quotation;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuotationFactory extends Factory
{
    protected $model = Quotation::class;

    public function definition(): array
    {
        return [
            'service_request_id' => ServiceRequest::factory(),
            'worker_id' => User::factory()->worker(),
            'base_price' => 100,
            'additional_charges' => 0,
            'discount' => 0,
            'tax' => 0,
            'total' => 100,
            'notes' => null,
            'status' => 'pending',
            'responded_at' => null,
        ];
    }
}
