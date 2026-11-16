<?php

namespace Tests\Feature\Customer;

use App\Models\Address;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * AI Service Assistant — Step 3: the booking-page integration. The assistant
 * is advisory chrome around the existing form — these tests lock in that the
 * existing category radios / description textarea / booking submission are
 * untouched, that nothing hidden or server-trusted was added, and that the
 * assistant itself is localized, RTL-safe, and reachable only where the
 * booking page itself already requires it.
 */
class AiAssistantBookingUiTest extends TestCase
{
    use RefreshDatabase;

    private function verifiedCustomerWithAddress(): User
    {
        $customer = User::factory()->customer()->create(['email_verified_at' => now()]);
        Address::factory()->for($customer, 'user')->create();

        return $customer;
    }

    public function test_the_assistant_is_present_on_the_booking_page_and_targets_the_right_endpoint(): void
    {
        ServiceCategory::factory()->create(['is_active' => true]);

        $html = $this->actingAs($this->verifiedCustomerWithAddress())
            ->get(route('customer.booking.create'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('aiSuggestion(', $html);
        // Rendered via Blade's @js(), which JSON-encodes (escaping slashes).
        $this->assertStringContainsString(addcslashes(route('customer.booking.aiSuggestion'), '/'), $html);
        $this->assertStringContainsString(__('ai_assistant.title'), $html);
        $this->assertStringContainsString(__('ai_assistant.action'), $html);
    }

    public function test_english_page_has_no_raw_ai_assistant_translation_keys(): void
    {
        ServiceCategory::factory()->create(['is_active' => true]);

        $this->actingAs($this->verifiedCustomerWithAddress())
            ->get(route('customer.booking.create'))
            ->assertOk()
            ->assertDontSee('ai_assistant.');
    }

    public function test_arabic_page_renders_rtl_with_localized_assistant_text_and_no_raw_keys(): void
    {
        ServiceCategory::factory()->create(['is_active' => true]);

        $this->actingAs($this->verifiedCustomerWithAddress())->withSession(['locale' => 'ar'])
            ->get(route('customer.booking.create'))
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee(__('ai_assistant.title', [], 'ar'))
            ->assertSee(__('ai_assistant.action', [], 'ar'))
            ->assertDontSee('ai_assistant.');
    }

    public function test_a_guest_cannot_reach_the_booking_page_or_the_assistant_on_it(): void
    {
        $this->get(route('customer.booking.create'))->assertRedirect(route('login'));
    }

    public function test_an_unverified_customer_cannot_reach_the_booking_page_or_the_assistant_on_it(): void
    {
        $customer = User::factory()->customer()->create(['email_verified_at' => null]);

        $this->actingAs($customer)
            ->get(route('customer.booking.create'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_the_existing_category_radios_are_unchanged_and_carry_the_matching_slug(): void
    {
        $categories = ServiceCategory::factory()->count(3)->create(['is_active' => true]);

        $html = $this->actingAs($this->verifiedCustomerWithAddress())
            ->get(route('customer.booking.create'))
            ->assertOk()
            ->getContent();

        $this->assertSame(3, substr_count($html, 'name="service_category_id"'));
        $this->assertSame(3, substr_count($html, 'data-category-option'));
        foreach ($categories as $category) {
            $this->assertStringContainsString('data-slug="'.$category->slug.'"', $html);
            $this->assertStringContainsString('value="'.$category->id.'"', $html);
        }
    }

    public function test_the_existing_problem_description_textarea_is_unchanged(): void
    {
        ServiceCategory::factory()->create(['is_active' => true]);

        $html = $this->actingAs($this->verifiedCustomerWithAddress())
            ->get(route('customer.booking.create'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="f-problem_description"', $html);
        $this->assertStringContainsString('name="problem_description"', $html);
    }

    public function test_no_hidden_worker_customer_or_financial_field_is_introduced(): void
    {
        ServiceCategory::factory()->create(['is_active' => true]);

        $html = $this->actingAs($this->verifiedCustomerWithAddress())
            ->get(route('customer.booking.create'))
            ->assertOk()
            ->getContent();

        foreach (['worker_id', 'customer_id', 'amount', 'final_total', 'quoted_total', 'status'] as $field) {
            $this->assertStringNotContainsString('name="'.$field.'"', $html);
        }
    }

    public function test_the_assistant_markup_never_uses_raw_unescaped_output(): void
    {
        $html = $this->actingAs($this->verifiedCustomerWithAddress())
            ->get(route('customer.booking.create'))
            ->assertOk()
            ->getContent();

        $assistantBlock = substr($html, (int) strpos($html, 'aiSuggestion('), strpos($html, '</form>') - strpos($html, 'aiSuggestion('));

        $this->assertStringNotContainsString('{!!', $assistantBlock);
        // The suggestion text binds via x-text (escaped by Alpine), never x-html.
        $this->assertStringContainsString('x-text="suggestion.description"', $html);
        $this->assertStringNotContainsString('x-html', $html);
    }

    public function test_the_assistant_has_accessible_labelling_and_alert_regions(): void
    {
        ServiceCategory::factory()->create(['is_active' => true]);

        $html = $this->actingAs($this->verifiedCustomerWithAddress())
            ->get(route('customer.booking.create'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('for="ai-assistant-description"', $html);
        $this->assertStringContainsString('id="ai-assistant-description"', $html);
        $this->assertStringContainsString('aria-live="polite"', $html);
        $this->assertSame(2, substr_count($html, 'role="alert"'));
    }

    public function test_booking_submission_is_completely_unchanged(): void
    {
        $customer = $this->verifiedCustomerWithAddress();
        $category = ServiceCategory::factory()->create(['is_active' => true]);
        $address = $customer->addresses()->first();

        $this->actingAs($customer)
            ->post(route('customer.booking.store'), [
                'service_category_id' => $category->id,
                'problem_description' => 'The kitchen sink is leaking.',
                'address_id' => $address->id,
                'preferred_date' => now()->addDay()->toDateString(),
                'preferred_time' => '10:00',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('service_requests', [
            'customer_id' => $customer->id,
            'service_category_id' => $category->id,
            'status' => 'pending',
        ]);
    }
}
