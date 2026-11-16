<?php

namespace Tests\Feature\React;

use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\WorkerProfile;
use App\Models\WorkerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * React foundation / Blade coexistence contract, verified from the server side:
 * the mount point, prop serialization, no data leakage, EN/AR/RTL, and that
 * non-React pages stay React-free.
 *
 * Phase 19: the canonical marketplace moved from /explore onto /professionals
 * (WorkerController::index), which now renders <x-react root="MarketplaceSearch">
 * with the full Blade marketplace as its no-JS fallback slot. The extended card
 * DTO (Phase 18) still excludes all worker PII and financial fields.
 */
class ReactFoundationTest extends TestCase
{
    use RefreshDatabase;

    private function visibleWorker(string $name = 'Nadia Fixit'): User
    {
        $worker = User::factory()->worker()->create([
            'name' => $name,
            'email' => 'secret.'.uniqid().'@example.test',
            'phone' => '+9613111222',
        ]);
        WorkerProfile::factory()->for($worker, 'user')->create([
            'verification_status' => 'verified',
            'rating_average' => 4.5,
            'rating_count' => 7,
            'experience_years' => 6,
        ]);
        WorkerService::create([
            'worker_id' => $worker->id,
            'service_category_id' => ServiceCategory::factory()->create(['name' => 'Plumbing'])->id,
            'price_from' => 40,
            'is_active' => true,
        ]);

        return $worker;
    }

    private function props(string $html): array
    {
        $this->assertMatchesRegularExpression('/data-react-root="MarketplaceSearch"/', $html);
        preg_match('/data-props="([^"]*)"/', $html, $m);
        $this->assertNotEmpty($m[1] ?? null, 'data-props attribute missing');

        $decoded = json_decode(html_entity_decode($m[1], ENT_QUOTES), true);
        $this->assertIsArray($decoded, 'data-props is not valid JSON');

        return $decoded;
    }

    public function test_professionals_renders_a_single_react_root_with_json_props(): void
    {
        $this->visibleWorker();

        $html = $this->get(route('workers.index'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'data-react-root='));
        $props = $this->props($html);

        $this->assertArrayHasKey('initialWorkers', $props);
        $this->assertArrayHasKey('initialMeta', $props);
        $this->assertArrayHasKey('initialFilters', $props);
        $this->assertArrayHasKey('strings', $props);
        $this->assertArrayHasKey('dir', $props);
        $this->assertArrayHasKey('api', $props);
        $this->assertSame('Nadia Fixit', $props['initialWorkers'][0]['name']);
        $this->assertSame(['Plumbing'], $props['initialWorkers'][0]['services']);
        $this->assertStringContainsString('/professionals/', $props['initialWorkers'][0]['profile_url']);
    }

    public function test_react_props_never_carry_sensitive_fields(): void
    {
        $this->visibleWorker();

        $props = $this->props($this->get(route('workers.index'))->assertOk()->getContent());
        $blob = json_encode($props);

        foreach (['email', 'phone', 'password', 'remember_token', 'api_token', 'whish', 'commission', 'payment_status'] as $needle) {
            $this->assertStringNotContainsStringIgnoringCase($needle, $blob, "leaked: {$needle}");
        }
        $this->assertStringNotContainsString('@example.test', $blob);

        $card = $props['initialWorkers'][0];
        $this->assertIsInt($card['id']);
        $this->assertStringEndsWith('/'.$card['id'], $card['profile_url']);
        $this->assertSame(
            ['id', 'name', 'verified', 'experience_years', 'rating', 'services',
                'availability', 'service_radius_km', 'photo_url', 'profile_url', 'favorite_url', 'favorited'],
            array_keys($card),
        );
        $this->assertContains($card['availability'], ['available_today', 'available', 'busy', 'unavailable']);
    }

    public function test_the_react_entry_script_loads_only_on_react_pages(): void
    {
        $this->visibleWorker();

        $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
        $reactChunk = $manifest['resources/js/react/index.jsx']['file'] ?? null;
        $alpineChunk = $manifest['resources/js/app.js']['file'] ?? null;
        $this->assertNotNull($reactChunk, 'react entry missing from the Vite manifest — run npm run build');
        $this->assertNotNull($alpineChunk);

        $marketplace = $this->get(route('workers.index'))->assertOk()->getContent();
        $home = $this->get('/')->assertOk()->getContent();

        // React ships on the marketplace (a <x-react> page) but not the homepage.
        $this->assertStringContainsString($reactChunk, $marketplace);
        $this->assertStringNotContainsString($reactChunk, $home);

        // Alpine ships on every page, including the React one.
        $this->assertStringContainsString($alpineChunk, $home);
        $this->assertStringContainsString($alpineChunk, $marketplace);
    }

    public function test_non_marketplace_pages_stay_react_free(): void
    {
        $this->get('/')->assertOk()->assertDontSee('data-react-root', false);
        $this->get(route('services.index'))->assertOk()->assertDontSee('data-react-root', false);
    }

    public function test_professionals_is_ltr_in_english(): void
    {
        $this->visibleWorker();
        $html = $this->get(route('workers.index'))->assertOk()
            ->assertSee('<html lang="en" dir="ltr">', false)
            ->getContent();

        $this->assertSame('ltr', $this->props($html)['dir']);
    }

    public function test_professionals_is_rtl_and_localized_in_arabic(): void
    {
        $this->visibleWorker();
        $html = $this->withSession(['locale' => 'ar'])
            ->get(route('workers.index'))->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee(__('workers.discover_title', [], 'ar'))
            ->getContent();

        $props = $this->props($html);
        $this->assertSame('rtl', $props['dir']);
        $this->assertSame('ar', $props['locale']);
        $this->assertSame(__('workers.discover_title', [], 'ar'), $props['strings']['search_placeholder']);
    }

    public function test_props_expose_the_api_url_and_viewer_flag_but_not_for_guests(): void
    {
        $worker = $this->visibleWorker();

        $guestProps = $this->props($this->get(route('workers.index'))->assertOk()->getContent());
        $this->assertFalse($guestProps['viewer']['isCustomer']);
        $this->assertSame(route('api.marketplace.workers'), $guestProps['api']['workers']);
        $this->assertFalse($guestProps['initialWorkers'][0]['favorited']);

        $customer = User::factory()->customer()->create();
        $this->actingAs($customer)->postJson(route('customer.favorites.toggle', $worker))->assertOk();

        $customerProps = $this->props(
            $this->actingAs($customer)->get(route('workers.index'))->assertOk()->getContent()
        );
        $this->assertTrue($customerProps['viewer']['isCustomer']);
        $this->assertTrue($customerProps['initialWorkers'][0]['favorited']);
    }

    public function test_the_no_js_fallback_is_the_full_blade_marketplace(): void
    {
        $this->visibleWorker('Fallback Fatima');

        $this->get(route('workers.index'))->assertOk()
            ->assertSee('Fallback Fatima')                                  // worker rendered server-side
            ->assertSee(route('workers.show', User::first()), false)        // real profile link
            ->assertSee('name="min_price"', false)                          // price filter present
            ->assertSee('name="min_rating"', false)                        // rating filter present
            ->assertSee(__('services.sort_label'));                         // sort control present
    }
}
