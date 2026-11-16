<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Database\Eloquent\Factories\Factory;

class RefundFactory extends Factory
{
    protected $model = Refund::class;

    public function definition(): array
    {
        return [
            'payment_id' => Payment::factory()->paid(),
            'amount' => 100.00,
            'type' => Refund::TYPE_FULL,
            'status' => Refund::STATUS_COMPLETED,
            'reason' => null,
            'processed_by' => null,
            'processed_at' => now(),
        ];
    }

    /** Mirror a real full refund of the given payment's captured amount. */
    public function ofPayment(Payment $payment): static
    {
        return $this->state(fn () => [
            'payment_id' => $payment->id,
            'amount' => $payment->amount,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => 'pending', 'processed_at' => null]);
    }

    public function failed(): static
    {
        return $this->state(fn () => ['status' => 'failed', 'processed_at' => null]);
    }
}
