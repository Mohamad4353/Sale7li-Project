<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $total = 100.00;

        return [
            'invoice_number' => 'INV-'.strtoupper(Str::random(8)),
            'service_request_id' => ServiceRequest::factory(),
            'customer_id' => User::factory()->customer(),
            'worker_id' => null,
            'line_items' => [['label' => 'Service', 'amount' => $total]],
            'subtotal' => $total,
            'discount' => 0,
            'tax' => 0,
            'total' => $total,
            'payment_status' => 'pending',
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => ['payment_status' => 'paid']);
    }
}
