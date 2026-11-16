<?php

namespace Tests\Feature\Payments;

use App\Models\Quotation;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The quotation money fields must only accept a plain money format. Laravel's
 * `numeric` rule alone accepts scientific notation ("1e3"), which the
 * integer-cent parser then rejects at runtime — a 500 instead of a 422.
 */
class QuotationInputValidationTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: ServiceRequest} */
    private function job(): array
    {
        $worker = User::factory()->worker()->create();
        $request = ServiceRequest::factory()
            ->for(User::factory()->customer()->create(), 'customer')
            ->status('accepted')
            ->create([
                'service_category_id' => ServiceCategory::factory()->create()->id,
                'worker_id' => $worker->id,
            ]);

        return [$worker, $request];
    }

    public function test_a_malformed_price_is_a_validation_error_not_a_server_error(): void
    {
        $cases = [
            ['base_price', '1e3'],      // scientific notation
            ['tax', '2e2'],            // scientific notation
            ['base_price', '0x1A'],    // hex
            ['base_price', '100.555'], // more than two decimal places
            ['discount', '10.001'],    // more than two decimal places
            ['base_price', '   '],     // whitespace
        ];

        foreach ($cases as [$field, $value]) {
            [$worker, $request] = $this->job();

            $this->actingAs($worker)
                ->from(route('worker.jobs.show', $request))
                ->post(route('worker.jobs.quotation', $request), ['base_price' => 100, $field => $value])
                ->assertSessionHasErrors($field);
        }

        $this->assertDatabaseCount('quotations', 0);
    }

    public function test_plain_integer_and_two_decimal_prices_are_accepted(): void
    {
        [$worker, $request] = $this->job();

        $this->actingAs($worker)->post(route('worker.jobs.quotation', $request), [
            'base_price' => '99.99',
            'additional_charges' => 20,
            'discount' => '10.50',
            'tax' => 5,
        ])->assertRedirect();

        // 99.99 + 20.00 - 10.50 + 5.00 = 114.49, server-derived
        $this->assertSame('114.49', Quotation::firstOrFail()->total);
    }
}
