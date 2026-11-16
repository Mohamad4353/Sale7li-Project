<?php

namespace Tests\Feature\Worker;

use App\Models\Quotation;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * P14-03 — worker quotation UX: monetary-amount labels, a client-side live
 * preview, and a "revise" context once a quotation exists. None of this touches
 * the server-side quotation calculation.
 */
class WorkerQuotationUiTest extends TestCase
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

    public function test_new_quotation_form_shows_amount_labels_and_a_live_preview(): void
    {
        [$worker, $request] = $this->job();

        $this->actingAs($worker)->get(route('worker.jobs.show', $request))
            ->assertOk()
            ->assertSee(__('workers.submit_quotation'))
            ->assertSee(__('workers.discount_amount'))
            ->assertSee(__('workers.tax_amount'))
            ->assertSee(__('workers.quote_preview_label'))
            ->assertSee('x-text="preview"', false)
            ->assertDontSee(__('workers.revise_quotation'));
    }

    public function test_form_switches_to_revise_context_once_a_quotation_exists(): void
    {
        [$worker, $request] = $this->job();

        $this->actingAs($worker)->post(route('worker.jobs.quotation', $request), [
            'base_price' => 100, 'additional_charges' => 20, 'discount' => 10, 'tax' => 5,
        ])->assertRedirect();

        $this->actingAs($worker)->get(route('worker.jobs.show', $request))
            ->assertOk()
            ->assertSee(__('workers.revise_quotation'))
            ->assertSee(__('workers.current_quotation'))
            ->assertSee(__('workers.send_revision'))
            ->assertSee('$115.00');   // server-derived total, shown in the summary
    }

    public function test_submitting_a_quotation_is_unchanged_and_stays_server_derived(): void
    {
        [$worker, $request] = $this->job();

        $this->actingAs($worker)->post(route('worker.jobs.quotation', $request), [
            'base_price' => '99.99', 'additional_charges' => 20, 'discount' => '10.50', 'tax' => 5,
            'total' => 1,   // ignored
        ])->assertRedirect();

        $quote = Quotation::where('service_request_id', $request->id)->firstOrFail();
        $this->assertSame('114.49', $quote->total);
        $this->assertDatabaseCount('quotations', 1);
    }

    public function test_quotation_form_renders_in_arabic(): void
    {
        [$worker, $request] = $this->job();

        $this->actingAs($worker)->withSession(['locale' => 'ar'])
            ->get(route('worker.jobs.show', $request))
            ->assertOk()
            ->assertSee(__('workers.quote_preview_label', [], 'ar'))
            ->assertSee(__('workers.discount_amount', [], 'ar'));
    }
}
