<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Quotation;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\WorkerProfile;
use App\Models\WorkerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoreServiceFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_marketplace_flow_from_booking_to_review(): void
    {
        $category = ServiceCategory::factory()->create(['base_price' => 40]);
        $customer = User::factory()->customer()->create();
        $admin = User::factory()->admin()->create();

        $worker = User::factory()->worker()->create();
        WorkerProfile::factory()->for($worker, 'user')->verified()->create();
        WorkerService::create([
            'worker_id' => $worker->id,
            'service_category_id' => $category->id,
            'price_from' => 40,
            'is_active' => true,
        ]);

        $address = Address::factory()->for($customer, 'user')->create();

        // 1. Customer books a service.
        $this->actingAs($customer)->post(route('customer.booking.store'), [
            'service_category_id' => $category->id,
            'problem_description' => 'Kitchen socket sparks when used.',
            'address_id' => $address->id,
            'preferred_date' => now()->addDays(2)->toDateString(),
            'preferred_time' => '09:30',
        ])->assertRedirect();

        $request = ServiceRequest::where('customer_id', $customer->id)->firstOrFail();
        $this->assertSame('pending', $request->status);
        $this->assertNull($request->worker_id);

        // 2. Admin assigns a qualified professional — job stays pending until accepted.
        $this->actingAs($admin)
            ->post(route('admin.requests.assign', $request), ['worker_id' => $worker->id])
            ->assertRedirect();
        $request->refresh();
        $this->assertSame($worker->id, $request->worker_id);
        $this->assertSame('pending', $request->status);

        // 3. Worker accepts.
        $this->actingAs($worker)->post(route('worker.jobs.accept', $request))->assertRedirect();
        $this->assertSame('accepted', $request->fresh()->status);

        // 4. Worker submits a quotation — not yet chargeable.
        $this->actingAs($worker)->post(route('worker.jobs.quotation', $request), [
            'base_price' => 90,
            'tax' => 10,
        ])->assertRedirect();
        $quotation = Quotation::where('service_request_id', $request->id)->firstOrFail();
        $this->assertSame('pending', $quotation->status);
        $this->assertNull($request->fresh()->quoted_total);

        // 5. Customer accepts the quotation.
        $this->actingAs($customer)->post(route('customer.quotations.accept', $quotation))->assertRedirect();
        $this->assertSame('accepted', $quotation->fresh()->status);
        $this->assertEqualsWithDelta(100.0, (float) $request->fresh()->quoted_total, 0.01);

        // 6. Worker schedules, travels, works, completes.
        $this->actingAs($worker)->post(route('worker.jobs.status', $request), [
            'status' => 'scheduled',
            'scheduled_date' => now()->addDays(2)->toDateString(),
            'scheduled_time' => '09:30',
        ])->assertRedirect();
        $this->assertSame('scheduled', $request->fresh()->status);
        $this->assertDatabaseHas('appointments', ['service_request_id' => $request->id, 'status' => 'scheduled']);

        foreach (['on_the_way', 'in_progress', 'completed'] as $next) {
            $this->actingAs($worker)->post(route('worker.jobs.status', $request), ['status' => $next])->assertRedirect();
        }
        $request->refresh();
        $this->assertSame('completed', $request->status);
        $this->assertEqualsWithDelta(100.0, (float) $request->final_total, 0.01);
        $this->assertNotNull($request->completed_at);
        $this->assertSame(1, $worker->workerProfile->fresh()->completed_jobs);

        // 7. Customer pays the accepted amount.
        $this->actingAs($customer)->post(route('customer.payments.pay', $request))->assertRedirect();
        $request->refresh();
        $this->assertSame('paid', $request->payment_status);
        $this->assertDatabaseHas('payments', ['service_request_id' => $request->id, 'status' => 'paid']);
        $this->assertDatabaseHas('invoices', ['service_request_id' => $request->id]);

        // 8. Customer leaves a review.
        $this->actingAs($customer)->post(route('customer.reviews.store', $request), [
            'rating' => 5,
            'comment' => 'Fast and tidy.',
        ])->assertRedirect();
        $this->assertDatabaseHas('reviews', ['service_request_id' => $request->id, 'rating' => 5]);
        $this->assertEqualsWithDelta(5.0, (float) $worker->workerProfile->fresh()->rating_average, 0.01);
    }
}
