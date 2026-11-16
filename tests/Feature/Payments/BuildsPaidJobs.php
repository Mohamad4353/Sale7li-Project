<?php

namespace Tests\Feature\Payments;

use App\Models\Quotation;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;

trait BuildsPaidJobs
{
    /**
     * A job in `accepted` state with a worker-submitted, customer-accepted
     * quotation. `quoted_total` is set the production way; `final_total` is not
     * yet frozen (that happens on completion).
     *
     * @param  array<string, mixed>  $components  quotation form fields
     * @return array{customer: User, worker: User, request: ServiceRequest, quotation: Quotation}
     */
    protected function quotedAcceptedJob(array $components = ['base_price' => 100]): array
    {
        $customer = User::factory()->customer()->create();
        $worker = User::factory()->worker()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('accepted')->create([
            'service_category_id' => ServiceCategory::factory()->create()->id,
            'worker_id' => $worker->id,
        ]);

        $this->actingAs($worker)
            ->post(route('worker.jobs.quotation', $request), $components)
            ->assertRedirect();

        $quotation = Quotation::where('service_request_id', $request->id)->firstOrFail();

        $this->actingAs($customer)
            ->post(route('customer.quotations.accept', $quotation))
            ->assertRedirect();

        return [
            'customer' => $customer,
            'worker' => $worker,
            'request' => $request->fresh(),
            'quotation' => $quotation->fresh(),
        ];
    }

    /**
     * The same job driven through the full worker status machine to `completed`,
     * so the workflow freezes `final_total`.
     *
     * @param  array<string, mixed>  $components
     * @return array{customer: User, worker: User, request: ServiceRequest, quotation: Quotation}
     */
    protected function quotedCompletedJob(array $components = ['base_price' => 100]): array
    {
        ['customer' => $customer, 'worker' => $worker, 'request' => $request, 'quotation' => $quotation]
            = $this->quotedAcceptedJob($components);

        $this->actingAs($worker)->post(route('worker.jobs.status', $request), [
            'status' => 'scheduled',
            'scheduled_date' => now()->addDay()->toDateString(),
            'scheduled_time' => '10:00',
        ])->assertRedirect();

        foreach (['on_the_way', 'in_progress', 'completed'] as $status) {
            $this->actingAs($worker)
                ->post(route('worker.jobs.status', $request), ['status' => $status])
                ->assertRedirect();
        }

        return [
            'customer' => $customer,
            'worker' => $worker,
            'request' => $request->fresh(),
            'quotation' => $quotation->fresh(),
        ];
    }
}
