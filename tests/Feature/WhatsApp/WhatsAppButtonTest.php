<?php

namespace Tests\Feature\WhatsApp;

use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\WorkerProfile;
use App\Models\WorkerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature 1 — "Contact on WhatsApp". A wa.me click-to-chat link with a
 * pre-filled, context-aware message. Hidden when there is no usable number.
 * The number only ever lives in the href, never as visible text, and cannot
 * be used to inject markup or change the destination.
 */
class WhatsAppButtonTest extends TestCase
{
    use RefreshDatabase;

    private function visibleWorker(array $attrs = [], string $category = 'Plumbing'): User
    {
        $worker = User::factory()->worker()->create($attrs);
        WorkerProfile::factory()->for($worker, 'user')->create(['verification_status' => 'verified']);
        WorkerService::create([
            'worker_id' => $worker->id,
            'service_category_id' => ServiceCategory::factory()->create(['name' => $category])->id,
            'price_from' => 40,
            'is_active' => true,
        ]);

        return $worker;
    }

    public function test_worker_profile_shows_a_whatsapp_link_with_a_prefilled_context_message(): void
    {
        $worker = $this->visibleWorker(['phone' => '+961 70 123 456'], 'Plumbing');

        $this->get(route('workers.show', $worker))
            ->assertOk()
            ->assertSee(__('whatsapp.contact'))
            ->assertSee('https://wa.me/96170123456?text=', false)
            ->assertSee(rawurlencode('ask about Plumbing'), false);
    }

    public function test_the_button_is_hidden_when_the_worker_has_no_usable_number(): void
    {
        $noPhone = $this->visibleWorker(['phone' => null]);
        $this->get(route('workers.show', $noPhone))->assertOk()->assertDontSee('wa.me', false);

        $tooShort = $this->visibleWorker(['phone' => '123']);
        $this->get(route('workers.show', $tooShort))->assertOk()->assertDontSee('wa.me', false);
    }

    public function test_the_link_is_safe_against_markup_or_destination_injection(): void
    {
        // Digits: 961 70 999 888 + the 1 from "alert(1)" => 961709998881
        $worker = $this->visibleWorker(['phone' => '+961-70-999-888 "><script>alert(1)</script>']);

        $html = $this->get(route('workers.show', $worker))->assertOk()->getContent();

        $this->assertStringContainsString('https://wa.me/961709998881?text=', $html);
        $this->assertStringNotContainsString('wa.me/+961', $html);
        $this->assertStringNotContainsString('wa.me/961-70', $html);
        // The script tag from the phone value never reaches an anchor href.
        $this->assertStringNotContainsString('wa.me/961709998881"><script>', $html);
    }

    public function test_support_whatsapp_button_uses_the_configured_number(): void
    {
        config()->set('sale7li.support_whatsapp', '9611234567');

        $this->actingAs(User::factory()->customer()->create())
            ->get(route('support'))
            ->assertOk()
            ->assertSee(__('whatsapp.contact_support'))
            ->assertSee('https://wa.me/9611234567?text=', false);
    }

    public function test_support_button_is_hidden_when_no_number_is_configured(): void
    {
        config()->set('sale7li.support_whatsapp', '');

        $this->actingAs(User::factory()->customer()->create())
            ->get(route('support'))
            ->assertOk()
            ->assertDontSee('wa.me', false);
    }

    public function test_the_prefilled_message_is_localised_for_arabic_visitors(): void
    {
        $worker = $this->visibleWorker(['phone' => '9613000111'], 'Cleaning');

        $this->withSession(['locale' => 'ar'])
            ->get(route('workers.show', $worker))
            ->assertOk()
            ->assertSee(rawurlencode('وجدت خدمتك على SALE7LI'), false);
    }

    /**
     * WhatsApp button visual identity: a recognizable brand icon, the existing
     * emerald "success" palette (not a new token), and an sr-only note that the
     * link opens WhatsApp in a new tab. None of this touches the href/target/rel
     * behaviour already covered above.
     */
    public function test_the_button_uses_the_whatsapp_icon_and_emerald_treatment(): void
    {
        $worker = $this->visibleWorker(['phone' => '9613000111']);

        $html = $this->get(route('workers.show', $worker))->assertOk()->getContent();

        $this->assertStringContainsString('bg-emerald-50', $html);
        $this->assertStringContainsString('text-emerald-700', $html);
        $this->assertStringContainsString(__('whatsapp.opens_new_tab'), $html);
        // Still opens in a new tab exactly as before.
        $this->assertStringContainsString('target="_blank"', $html);
        $this->assertStringContainsString('rel="noopener noreferrer"', $html);
    }

    public function test_the_default_label_reads_contact_us_on_whatsapp(): void
    {
        $worker = $this->visibleWorker(['phone' => '9613000111']);

        $this->get(route('workers.show', $worker))
            ->assertOk()
            ->assertSee(__('whatsapp.contact'));

        $this->assertSame('Contact us on WhatsApp', __('whatsapp.contact', [], 'en'));
        $this->assertSame('تواصل معنا عبر واتساب', __('whatsapp.contact', [], 'ar'));
    }
}
