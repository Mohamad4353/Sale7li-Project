<?php

namespace Tests\Feature\Unit;

use App\Models\Address;
use App\Models\Payment;
use App\Models\Quotation;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\AiCategorySuggestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * AI Service Assistant — Step 1 (backend only). Gemini is called through
 * Illuminate\Support\Facades\Http and ALWAYS faked here — no test ever reaches
 * the real provider. The service is advisory-only: it never touches
 * ServiceRequest/Payment/Quotation and never trusts the model's category value
 * as anything more than a string to look up in the caller-supplied active list.
 */
class AiCategorySuggestionServiceTest extends TestCase
{
    use RefreshDatabase;

    private function fakeKey(): void
    {
        config(['services.ai_assistant.key' => 'test-fake-key', 'services.ai_assistant.model' => 'gemini-test']);
    }

    private function activeCategories(): Collection
    {
        return ServiceCategory::factory()->count(2)->create(['is_active' => true])
            ->push(ServiceCategory::factory()->create(['is_active' => true, 'slug' => 'plumbing', 'name' => 'Plumbing']));
    }

    private function geminiTextResponse(array $payload, int $status = 200)
    {
        return Http::response([
            'candidates' => [
                ['content' => ['parts' => [['text' => json_encode($payload)]]]],
            ],
        ], $status);
    }

    public function test_a_valid_active_category_is_accepted(): void
    {
        $this->fakeKey();
        $categories = $this->activeCategories();
        Http::fake(['generativelanguage.googleapis.com/*' => $this->geminiTextResponse([
            'category_slug' => 'plumbing',
            'structured_description' => 'A pipe under the kitchen sink is leaking.',
        ])]);

        $result = (new AiCategorySuggestionService)->suggest('my sink leaks', $categories, 'en');

        $this->assertTrue($result->available);
        $this->assertSame('plumbing', $result->categorySlug);
        $this->assertSame('A pipe under the kitchen sink is leaking.', $result->structuredDescription);
    }

    public function test_an_unknown_category_slug_is_rejected_not_invented(): void
    {
        $this->fakeKey();
        $categories = $this->activeCategories();
        Http::fake(['generativelanguage.googleapis.com/*' => $this->geminiTextResponse([
            'category_slug' => 'nuclear-physics', // not in the supplied catalogue
            'structured_description' => 'Unrelated text.',
        ])]);

        $result = (new AiCategorySuggestionService)->suggest('something odd', $categories, 'en');

        $this->assertTrue($result->available);
        $this->assertNull($result->categorySlug);
    }

    public function test_an_inactive_categorys_slug_is_rejected(): void
    {
        $this->fakeKey();
        $categories = $this->activeCategories(); // does not include this one
        ServiceCategory::factory()->create(['is_active' => false, 'slug' => 'retired-service']);
        Http::fake(['generativelanguage.googleapis.com/*' => $this->geminiTextResponse([
            'category_slug' => 'retired-service',
            'structured_description' => 'x',
        ])]);

        $result = (new AiCategorySuggestionService)->suggest('x', $categories, 'en');

        $this->assertTrue($result->available);
        $this->assertNull($result->categorySlug);
    }

    public function test_a_malformed_provider_response_fails_safely(): void
    {
        $this->fakeKey();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['candidates' => []], 200)]);

        $result = (new AiCategorySuggestionService)->suggest('x', $this->activeCategories(), 'en');

        $this->assertFalse($result->available);
        $this->assertNull($result->categorySlug);
        $this->assertNull($result->structuredDescription);
    }

    public function test_non_json_model_text_fails_safely(): void
    {
        $this->fakeKey();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => 'not json at all']]]]],
        ], 200)]);

        $result = (new AiCategorySuggestionService)->suggest('x', $this->activeCategories(), 'en');

        $this->assertFalse($result->available);
    }

    public function test_a_provider_http_error_fails_safely(): void
    {
        $this->fakeKey();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => 'boom'], 500)]);

        $result = (new AiCategorySuggestionService)->suggest('x', $this->activeCategories(), 'en');

        $this->assertFalse($result->available);
    }

    public function test_a_provider_timeout_or_connection_exception_fails_safely(): void
    {
        $this->fakeKey();
        Http::fake(['generativelanguage.googleapis.com/*' => fn () => throw new ConnectionException('timed out')]);

        $result = (new AiCategorySuggestionService)->suggest('x', $this->activeCategories(), 'en');

        $this->assertFalse($result->available);
    }

    public function test_a_missing_api_key_fails_safely_without_calling_the_provider(): void
    {
        config(['services.ai_assistant.key' => null]);
        Http::fake();

        $result = (new AiCategorySuggestionService)->suggest('x', $this->activeCategories(), 'en');

        $this->assertFalse($result->available);
        Http::assertNothingSent();
    }

    public function test_arabic_structured_description_is_preserved_verbatim(): void
    {
        $this->fakeKey();
        $categories = $this->activeCategories();
        $arabicText = 'أنبوب تحت المغسلة يسرب الماء.';
        Http::fake(['generativelanguage.googleapis.com/*' => $this->geminiTextResponse([
            'category_slug' => 'plumbing',
            'structured_description' => $arabicText,
        ])]);

        $result = (new AiCategorySuggestionService)->suggest('المغسلة تسرب', $categories, 'ar');

        $this->assertTrue($result->available);
        $this->assertSame($arabicText, $result->structuredDescription);
    }

    public function test_the_outbound_payload_contains_only_the_description_catalogue_and_locale(): void
    {
        $this->fakeKey();
        $categories = $this->activeCategories();

        // A real customer exists with sensitive fields that must never be sent —
        // proves the service reaches for nothing beyond its own parameters.
        $customer = User::factory()->customer()->create([
            'email' => 'do-not-leak@example.test',
            'phone' => '+96170000000',
        ]);
        Address::factory()->for($customer, 'user')->create(['street' => 'Do Not Leak Street 123']);

        Http::fake(['generativelanguage.googleapis.com/*' => $this->geminiTextResponse([
            'category_slug' => 'plumbing',
            'structured_description' => 'ok',
        ])]);

        (new AiCategorySuggestionService)->suggest('my sink is broken', $categories, 'en');

        Http::assertSent(function ($request) use ($categories) {
            $body = json_encode($request->data());

            $this->assertStringContainsString('my sink is broken', $body);
            foreach ($categories as $category) {
                $this->assertStringContainsString($category->slug, $body);
            }
            $this->assertStringNotContainsString('do-not-leak@example.test', $body);
            $this->assertStringNotContainsString('+96170000000', $body);
            $this->assertStringNotContainsString('Do Not Leak Street', $body);

            return true;
        });
    }

    public function test_the_service_never_creates_service_requests_payments_or_quotations(): void
    {
        $this->fakeKey();
        Http::fake(['generativelanguage.googleapis.com/*' => $this->geminiTextResponse([
            'category_slug' => 'plumbing',
            'structured_description' => 'ok',
        ])]);

        (new AiCategorySuggestionService)->suggest('x', $this->activeCategories(), 'en');

        $this->assertSame(0, ServiceRequest::count());
        $this->assertSame(0, Payment::count());
        $this->assertSame(0, Quotation::count());
    }

    public function test_an_empty_active_category_list_fails_safely_without_calling_the_provider(): void
    {
        $this->fakeKey();
        Http::fake();

        $result = (new AiCategorySuggestionService)->suggest('x', collect(), 'en');

        $this->assertFalse($result->available);
        Http::assertNothingSent();
    }
}
