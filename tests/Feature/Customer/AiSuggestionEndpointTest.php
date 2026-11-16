<?php

namespace Tests\Feature\Customer;

use App\Models\Notification;
use App\Models\Payment;
use App\Models\Quotation;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * AI Service Assistant — Step 2: the customer-facing endpoint. Advisory only —
 * it must never create/modify a request, quotation, payment, or notification,
 * and the model's category value is only ever surfaced after
 * AiCategorySuggestionService has matched it against the active catalogue.
 * Gemini is always faked; no test reaches the real provider.
 */
class AiSuggestionEndpointTest extends TestCase
{
    use RefreshDatabase;

    private function fakeKey(): void
    {
        config(['services.ai_assistant.key' => 'test-fake-key-do-not-leak', 'services.ai_assistant.model' => 'gemini-test']);
    }

    private function verifiedCustomer(): User
    {
        return User::factory()->customer()->create(['email_verified_at' => now()]);
    }

    private function geminiResponse(?string $slug, string $description): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [
                ['content' => ['parts' => [['text' => json_encode([
                    'category_slug' => $slug,
                    'structured_description' => $description,
                ])]]]],
            ],
        ], 200)]);
    }

    /* ------------------------------ access ------------------------------ */

    public function test_a_guest_is_blocked(): void
    {
        $this->post(route('customer.booking.aiSuggestion'), ['description' => 'x'])
            ->assertRedirect(route('login'));
    }

    public function test_a_verified_customer_is_allowed(): void
    {
        $this->fakeKey();
        ServiceCategory::factory()->create(['is_active' => true]);
        $this->geminiResponse(null, 'A clearer description.');

        $this->actingAs($this->verifiedCustomer())
            ->postJson(route('customer.booking.aiSuggestion'), ['description' => 'My AC runs but the room stays warm.'])
            ->assertOk();
    }

    public function test_an_unverified_customer_is_blocked(): void
    {
        $customer = User::factory()->customer()->create(['email_verified_at' => null]);

        $this->actingAs($customer)
            ->post(route('customer.booking.aiSuggestion'), ['description' => 'x'])
            ->assertRedirect(route('verification.notice'));
    }

    public function test_a_worker_is_blocked(): void
    {
        $this->actingAs(User::factory()->worker()->create())
            ->post(route('customer.booking.aiSuggestion'), ['description' => 'x'])
            ->assertForbidden();
    }

    public function test_an_admin_is_blocked(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('customer.booking.aiSuggestion'), ['description' => 'x'])
            ->assertForbidden();
    }

    public function test_a_suspended_customer_is_blocked(): void
    {
        $customer = User::factory()->customer()->create(['email_verified_at' => now(), 'status' => 'suspended']);

        $this->actingAs($customer)
            ->postJson(route('customer.booking.aiSuggestion'), ['description' => 'x'])
            ->assertForbidden();
    }

    public function test_the_endpoint_only_accepts_post(): void
    {
        $this->actingAs($this->verifiedCustomer())
            ->get(route('customer.booking.aiSuggestion'))
            ->assertStatus(405);
    }

    /* ---------------------------- validation ----------------------------- */

    public function test_an_empty_description_is_rejected(): void
    {
        $this->actingAs($this->verifiedCustomer())
            ->post(route('customer.booking.aiSuggestion'), ['description' => ''])
            ->assertSessionHasErrors('description');
    }

    public function test_a_description_over_2000_characters_is_rejected(): void
    {
        $this->actingAs($this->verifiedCustomer())
            ->post(route('customer.booking.aiSuggestion'), ['description' => str_repeat('a', 2001)])
            ->assertSessionHasErrors('description');
    }

    /* ------------------------------ contract ----------------------------- */

    public function test_a_valid_request_returns_the_expected_json_contract(): void
    {
        $this->fakeKey();
        $category = ServiceCategory::factory()->create(['is_active' => true, 'slug' => 'plumbing', 'name' => 'Plumbing']);
        $this->geminiResponse('plumbing', 'A pipe under the sink is leaking.');

        $this->actingAs($this->verifiedCustomer())
            ->postJson(route('customer.booking.aiSuggestion'), ['description' => 'sink leaks'])
            ->assertOk()
            ->assertExactJson([
                'available' => true,
                'category' => ['slug' => 'plumbing', 'label' => $category->localized_name],
                'structured_description' => 'A pipe under the sink is leaking.',
            ]);
    }

    public function test_the_category_label_is_resolved_server_side_via_the_localized_accessor(): void
    {
        $this->fakeKey();
        $category = ServiceCategory::factory()->create([
            'is_active' => true, 'slug' => 'electrical', 'name' => 'Electrical', 'name_ar' => 'كهرباء',
        ]);
        $this->geminiResponse('electrical', 'An outlet sparks when used.');

        $json = $this->actingAs($this->verifiedCustomer())
            ->postJson(route('customer.booking.aiSuggestion'), ['description' => 'sparks'])
            ->assertOk()->json();

        $this->assertSame($category->localized_name, $json['category']['label']);
        $this->assertNotSame($category->name_ar, $json['category']['label']); // en by default
    }

    public function test_an_arabic_locale_returns_the_arabic_category_label(): void
    {
        $this->fakeKey();
        ServiceCategory::factory()->create([
            'is_active' => true, 'slug' => 'electrical', 'name' => 'Electrical', 'name_ar' => 'كهرباء',
        ]);
        $this->geminiResponse('electrical', 'وصف أوضح للمشكلة.');

        $json = $this->actingAs($this->verifiedCustomer())->withSession(['locale' => 'ar'])
            ->postJson(route('customer.booking.aiSuggestion'), ['description' => 'مشكلة كهرباء'])
            ->assertOk()->json();

        $this->assertSame('كهرباء', $json['category']['label']);
    }

    public function test_an_invalid_provider_category_does_not_become_a_valid_category(): void
    {
        $this->fakeKey();
        ServiceCategory::factory()->create(['is_active' => true, 'slug' => 'plumbing']);
        $this->geminiResponse('made-up-category', 'Some description.');

        $this->actingAs($this->verifiedCustomer())
            ->postJson(route('customer.booking.aiSuggestion'), ['description' => 'x'])
            ->assertOk()
            ->assertExactJson([
                'available' => true,
                'category' => null,
                'structured_description' => 'Some description.',
            ]);
    }

    public function test_an_inactive_categorys_slug_does_not_become_a_valid_category(): void
    {
        $this->fakeKey();
        ServiceCategory::factory()->create(['is_active' => true, 'slug' => 'plumbing']);
        ServiceCategory::factory()->create(['is_active' => false, 'slug' => 'retired']);
        $this->geminiResponse('retired', 'x');

        $json = $this->actingAs($this->verifiedCustomer())
            ->postJson(route('customer.booking.aiSuggestion'), ['description' => 'x'])
            ->assertOk()->json();

        $this->assertNull($json['category']);
    }

    /* ------------------------------ failure ------------------------------ */

    public function test_provider_failure_returns_a_controlled_unavailable_response(): void
    {
        $this->fakeKey();
        ServiceCategory::factory()->create(['is_active' => true]);
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => 'boom'], 500)]);

        $this->actingAs($this->verifiedCustomer())
            ->postJson(route('customer.booking.aiSuggestion'), ['description' => 'x'])
            ->assertOk()
            ->assertExactJson(['available' => false]);
    }

    public function test_a_provider_exception_does_not_become_a_500(): void
    {
        $this->fakeKey();
        ServiceCategory::factory()->create(['is_active' => true]);
        Http::fake(['generativelanguage.googleapis.com/*' => fn () => throw new ConnectionException('timed out')]);

        $this->actingAs($this->verifiedCustomer())
            ->postJson(route('customer.booking.aiSuggestion'), ['description' => 'x'])
            ->assertOk()
            ->assertExactJson(['available' => false]);
    }

    public function test_provider_error_details_and_the_api_key_are_never_exposed(): void
    {
        $this->fakeKey();
        ServiceCategory::factory()->create(['is_active' => true]);
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'error' => ['message' => 'invalid api key SUPER-SECRET-DETAIL', 'code' => 400],
        ], 400)]);

        $content = $this->actingAs($this->verifiedCustomer())
            ->postJson(route('customer.booking.aiSuggestion'), ['description' => 'x'])
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('SUPER-SECRET-DETAIL', $content);
        $this->assertStringNotContainsString('test-fake-key-do-not-leak', $content);
        $this->assertSame(['available' => false], json_decode($content, true));
    }

    /* --------------------------- rate limiting ---------------------------- */

    public function test_the_rate_limit_returns_429_after_five_requests_per_minute(): void
    {
        $this->fakeKey();
        ServiceCategory::factory()->create(['is_active' => true]);
        $this->geminiResponse(null, 'ok');
        $customer = $this->verifiedCustomer();

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($customer)
                ->postJson(route('customer.booking.aiSuggestion'), ['description' => 'x'])
                ->assertOk();
        }

        $this->actingAs($customer)
            ->postJson(route('customer.booking.aiSuggestion'), ['description' => 'x'])
            ->assertStatus(429);
    }

    public function test_the_rate_limit_is_keyed_per_customer_not_shared(): void
    {
        $this->fakeKey();
        ServiceCategory::factory()->create(['is_active' => true]);
        $this->geminiResponse(null, 'ok');
        $exhausted = $this->verifiedCustomer();
        $other = $this->verifiedCustomer();

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($exhausted)
                ->postJson(route('customer.booking.aiSuggestion'), ['description' => 'x'])
                ->assertOk();
        }
        $this->actingAs($exhausted)
            ->postJson(route('customer.booking.aiSuggestion'), ['description' => 'x'])
            ->assertStatus(429);

        // A different authenticated customer still has their own budget.
        $this->actingAs($other)
            ->postJson(route('customer.booking.aiSuggestion'), ['description' => 'x'])
            ->assertOk();
    }

    /* ------------------------------ isolation ----------------------------- */

    public function test_a_posted_customer_id_is_ignored_and_grants_no_access_to_anyone_elses_data(): void
    {
        $this->fakeKey();
        ServiceCategory::factory()->create(['is_active' => true]);
        $stranger = User::factory()->customer()->create();
        $this->geminiResponse(null, 'ok');

        $json = $this->actingAs($this->verifiedCustomer())
            ->postJson(route('customer.booking.aiSuggestion'), [
                'description' => 'x',
                'customer_id' => $stranger->id,
                'user_id' => $stranger->id,
            ])
            ->assertOk()->json();

        // The response never carries any customer/user identity at all.
        $this->assertArrayNotHasKey('customer_id', $json);
        $this->assertArrayNotHasKey('user_id', $json);
        $this->assertArrayNotHasKey('id', $json);
    }

    /* ------------------------------ no side effects ------------------------------ */

    public function test_the_endpoint_never_creates_a_service_request_payment_quotation_or_notification(): void
    {
        $this->fakeKey();
        ServiceCategory::factory()->create(['is_active' => true, 'slug' => 'plumbing']);
        $this->geminiResponse('plumbing', 'ok');

        $this->actingAs($this->verifiedCustomer())
            ->postJson(route('customer.booking.aiSuggestion'), ['description' => 'x'])
            ->assertOk();

        $this->assertSame(0, ServiceRequest::count());
        $this->assertSame(0, Payment::count());
        $this->assertSame(0, Quotation::count());
        $this->assertSame(0, Notification::count());
    }

    /**
     * A hostile or buggy provider response could carry extra keys shaped like
     * application data (price, worker_id, status...). The controller only ever
     * reads `category_slug` / `structured_description` off the decoded JSON —
     * everything else must be silently ignored, never leaked in the response
     * and never actioned against the database.
     */
    public function test_unexpected_additional_provider_fields_are_ignored_and_cause_no_side_effects(): void
    {
        $this->fakeKey();
        ServiceCategory::factory()->create(['is_active' => true, 'slug' => 'plumbing', 'name' => 'Plumbing']);
        $worker = User::factory()->worker()->create();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [
                ['content' => ['parts' => [['text' => json_encode([
                    'category_slug' => 'plumbing',
                    'structured_description' => 'ok',
                    'price' => 999,
                    'worker_id' => $worker->id,
                    'status' => 'completed',
                    'final_total' => '500.00',
                ])]]]],
            ],
        ], 200)]);

        $content = $this->actingAs($this->verifiedCustomer())
            ->postJson(route('customer.booking.aiSuggestion'), ['description' => 'x'])
            ->assertOk()
            ->getContent();

        $this->assertSame([
            'available' => true,
            'category' => ['slug' => 'plumbing', 'label' => 'Plumbing'],
            'structured_description' => 'ok',
        ], json_decode($content, true));

        $this->assertSame(0, ServiceRequest::count());
        $this->assertSame(0, Payment::count());
        $this->assertSame(0, Quotation::count());
    }

    /**
     * The whole HTTP response body — not just the inner candidate text — may
     * itself fail to decode as JSON (e.g. an HTML error page served with a 200
     * by an intermediary). This must fail closed exactly like every other
     * malformed-response case.
     */
    public function test_a_completely_non_json_top_level_response_fails_safely(): void
    {
        $this->fakeKey();
        ServiceCategory::factory()->create(['is_active' => true]);
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response('<html>not json</html>', 200)]);

        $this->actingAs($this->verifiedCustomer())
            ->postJson(route('customer.booking.aiSuggestion'), ['description' => 'x'])
            ->assertOk()
            ->assertExactJson(['available' => false]);
    }

    public function test_no_active_categories_fails_safely_without_calling_the_provider(): void
    {
        $this->fakeKey();
        ServiceCategory::factory()->create(['is_active' => false]); // exists, but not active
        Http::fake();

        $this->actingAs($this->verifiedCustomer())
            ->postJson(route('customer.booking.aiSuggestion'), ['description' => 'x'])
            ->assertOk()
            ->assertExactJson(['available' => false]);

        Http::assertNothingSent();
    }
}
