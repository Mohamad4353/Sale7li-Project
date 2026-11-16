<?php

namespace Tests\Feature\Customer;

use App\Models\Quotation;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * P14-06 / P14-12 / P14-16 — customer-facing payment & quotation polish:
 * WHISH copy controls, quotation line-item breakdown, and a confirmation on the
 * accept-quote and instant-pay actions (existing data-confirm mechanism).
 * No financial calculation is added or changed.
 */
class QuotationAndPaymentUiTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: ServiceRequest, 2: Quotation} */
    private function pendingQuote(array $fields = ['base_price' => 100, 'additional_charges' => 20, 'discount' => 10, 'tax' => 5]): array
    {
        $customer = User::factory()->customer()->create();
        $worker = User::factory()->worker()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('accepted')->create([
            'service_category_id' => ServiceCategory::factory()->create()->id,
            'worker_id' => $worker->id,
        ]);
        $this->actingAs($worker)->post(route('worker.jobs.quotation', $request), $fields)->assertRedirect();

        return [$customer, $request, Quotation::where('service_request_id', $request->id)->firstOrFail()];
    }

    public function test_customer_sees_the_quotation_line_items_and_total(): void
    {
        [$customer, $request, $quote] = $this->pendingQuote();

        $this->actingAs($customer)->get(route('customer.requests.show', $request))
            ->assertOk()
            ->assertSee(__('requests.quote_base'))
            ->assertSee(__('requests.quote_additional'))
            ->assertSee(__('requests.quote_discount'))
            ->assertSee(__('requests.quote_tax'))
            ->assertSee('$100.00')   // base
            ->assertSee('$115.00');  // existing quotation total (not recomputed)
    }

    public function test_accept_button_carries_a_confirmation_with_the_quotation_total(): void
    {
        [$customer, $request, $quote] = $this->pendingQuote();

        $this->actingAs($customer)->get(route('customer.requests.show', $request))
            ->assertOk()
            ->assertSee('data-confirm="'.e(__('requests.accept_confirm', ['amount' => '$115.00'])).'"', false);
    }

    public function test_worker_note_is_shown_when_present(): void
    {
        [$customer, $request] = $this->pendingQuote([
            'base_price' => 80, 'notes' => 'Parts need ordering, 2-day lead time.',
        ]);

        $this->actingAs($customer)->get(route('customer.requests.show', $request))
            ->assertOk()
            ->assertSee(__('requests.quote_notes'))
            ->assertSee('Parts need ordering, 2-day lead time.');
    }

    public function test_instant_pay_button_carries_a_confirmation(): void
    {
        [$customer, $request, $quote] = $this->pendingQuote();
        $this->actingAs($customer)->post(route('customer.quotations.accept', $quote))->assertRedirect();

        $this->actingAs($customer)->get(route('customer.requests.show', $request))
            ->assertOk()
            ->assertSee('data-confirm="'.e(__('payments.pay_confirm', ['amount' => '$115.00'])).'"', false)
            ->assertSee(__('payments.pay_securely'));
    }

    public function test_whish_screen_shows_copy_controls_without_being_required(): void
    {
        [$customer, $request, $quote] = $this->pendingQuote();
        $this->actingAs($customer)->post(route('customer.quotations.accept', $quote))->assertRedirect();

        $this->actingAs($customer)->get(route('customer.payments.whish', $request))
            ->assertOk()
            ->assertSee(__('messages.copy'))
            ->assertSee(__('payments.copy_account_number'))
            ->assertSee('navigator.clipboard', false)
            // the submit form and its reference field are still present regardless of clipboard
            ->assertSee('name="whish_reference"', false);
    }

    public function test_quotation_breakdown_renders_in_arabic(): void
    {
        [$customer, $request] = $this->pendingQuote();

        $this->actingAs($customer)->withSession(['locale' => 'ar'])
            ->get(route('customer.requests.show', $request))
            ->assertOk()
            ->assertSee(__('requests.quote_base', [], 'ar'))
            ->assertSee(__('requests.quote_tax', [], 'ar'));
    }
}
