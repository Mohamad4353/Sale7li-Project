<?php

namespace Tests\Feature\Worker;

use App\Models\Quotation;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * P14-10 — worker job detail contact completeness: a tel: link and a WhatsApp
 * button for the customer, shown only to the assigned (already authorized)
 * worker. No authorization logic is changed.
 */
class JobDetailContactTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: ServiceRequest} */
    private function job(array $customerAttrs = ['phone' => '+961 70 555 111']): array
    {
        $worker = User::factory()->worker()->create();
        $request = ServiceRequest::factory()
            ->for(User::factory()->customer()->create($customerAttrs), 'customer')
            ->status('accepted')
            ->create([
                'service_category_id' => ServiceCategory::factory()->create(['name' => 'Plumbing'])->id,
                'worker_id' => $worker->id,
            ]);

        return [$worker, $request];
    }

    public function test_assigned_worker_sees_a_tel_link_and_a_whatsapp_button(): void
    {
        [$worker, $request] = $this->job();

        $this->actingAs($worker)->get(route('worker.jobs.show', $request))->assertOk()
            ->assertSee('href="tel:+96170555111"', false)
            ->assertSee('https://wa.me/96170555111?text=', false)
            ->assertSee(__('workers.whatsapp_customer'));
    }

    public function test_no_contact_controls_when_the_customer_has_no_phone(): void
    {
        [$worker, $request] = $this->job(['phone' => null]);

        $this->actingAs($worker)->get(route('worker.jobs.show', $request))->assertOk()
            ->assertDontSee('href="tel:', false)
            ->assertDontSee('wa.me', false)
            ->assertSee(__('workers.no_phone'));
    }

    public function test_an_unrelated_worker_cannot_reach_the_job_or_its_contact_info(): void
    {
        [, $request] = $this->job();
        $intruder = User::factory()->worker()->create();

        $this->actingAs($intruder)->get(route('worker.jobs.show', $request))->assertForbidden();
    }

    public function test_existing_quotation_is_shown_with_server_derived_total(): void
    {
        [$worker, $request] = $this->job();
        $this->actingAs($worker)->post(route('worker.jobs.quotation', $request), [
            'base_price' => 100, 'additional_charges' => 20, 'discount' => 10, 'tax' => 5,
        ])->assertRedirect();

        $quote = Quotation::where('service_request_id', $request->id)->firstOrFail();

        $this->actingAs($worker)->get(route('worker.jobs.show', $request))->assertOk()
            ->assertSee(__('workers.revise_quotation'))
            ->assertSee(__('workers.current_quotation'))
            ->assertSee('$'.number_format((float) $quote->total, 2));
    }
}
