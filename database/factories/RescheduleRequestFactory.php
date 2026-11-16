<?php

namespace Database\Factories;

use App\Models\RescheduleRequest;
use App\Models\ServiceRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

class RescheduleRequestFactory extends Factory
{
    protected $model = RescheduleRequest::class;

    public function definition(): array
    {
        return [
            'service_request_id' => ServiceRequest::factory(),
            'proposed_date' => now()->addDays(3)->toDateString(),
            'proposed_time' => '14:00',
            'reason' => null,
            'status' => 'pending',
            'decline_reason' => null,
            'responded_at' => null,
        ];
    }
}
