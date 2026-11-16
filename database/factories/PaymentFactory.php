<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'service_request_id' => ServiceRequest::factory(),
            'customer_id' => User::factory()->customer(),
            'worker_id' => null,
            'transaction_reference' => 'TEST-'.strtoupper(Str::random(12)),
            'amount' => 100.00,
            'commission_rate' => null,
            'commission_amount' => null,
            'worker_net' => null,
            'currency' => 'USD',
            'status' => 'pending',
            'paid_at' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => ['status' => 'paid', 'paid_at' => now()]);
    }

    /** Pure data state — no refund logic is implemented in this step. */
    public function refunded(): static
    {
        return $this->state(fn () => ['status' => 'refunded', 'paid_at' => now()]);
    }
}
