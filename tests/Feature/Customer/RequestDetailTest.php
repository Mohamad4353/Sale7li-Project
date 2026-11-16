<?php

namespace Tests\Feature\Customer;

use App\Models\Address;
use App\Models\Appointment;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\WorkerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 35.2 — the enhanced customer request detail page: a current-progress
 * summary above the timeline, plus a request summary block. All existing
 * business actions and authorization are unchanged.
 */
class RequestDetailTest extends TestCase
{
    use RefreshDatabase;

    private function ownedRequest(array $attrs = []): array
    {
        $customer = User::factory()->customer()->create();
        $category = ServiceCategory::factory()->create(['name' => 'Electrical Repairs']);
        $address = Address::factory()->create([
            'user_id' => $customer->id, 'street' => '14 Rue Gouraud', 'city' => 'Beirut',
        ]);
        $request = ServiceRequest::factory()->for($customer, 'customer')->create([
            'service_category_id' => $category->id,
            'address_id' => $address->id,
            'preferred_time' => '10:00',
            ...$attrs,
        ]);

        return [$customer, $request];
    }

    public function test_the_progress_card_explains_the_current_status(): void
    {
        [$customer, $request] = $this->ownedRequest(['status' => 'in_progress']);

        $this->actingAs($customer)->get(route('customer.requests.show', $request))
            ->assertOk()
            ->assertSee(__('requests.progress_hint.in_progress'));
    }

    public function test_an_assigned_but_unconfirmed_request_uses_the_assigned_hint(): void
    {
        [$customer, $request] = $this->ownedRequest(['status' => 'pending']);
        $worker = User::factory()->worker()->create();
        WorkerProfile::factory()->for($worker, 'user')->verified()->create();
        $request->update(['worker_id' => $worker->id]);

        $this->actingAs($customer)->get(route('customer.requests.show', $request))
            ->assertOk()
            ->assertSee(__('requests.progress_hint.assigned'))
            ->assertDontSee(__('requests.progress_hint.pending'));
    }

    public function test_an_unassigned_request_uses_the_pending_hint(): void
    {
        [$customer, $request] = $this->ownedRequest(['status' => 'pending']);

        $this->actingAs($customer)->get(route('customer.requests.show', $request))
            ->assertOk()
            ->assertSee(__('requests.progress_hint.pending'));
    }

    public function test_the_summary_shows_the_customers_own_request_details(): void
    {
        [$customer, $request] = $this->ownedRequest(['status' => 'accepted']);

        $this->actingAs($customer)->get(route('customer.requests.show', $request))
            ->assertOk()
            ->assertSee(__('requests.service_address'))
            ->assertSee('14 Rue Gouraud, Beirut')
            ->assertSee($request->reference)
            ->assertSee('Electrical Repairs');
    }

    public function test_the_progress_card_localises_to_arabic(): void
    {
        [$customer, $request] = $this->ownedRequest(['status' => 'scheduled']);

        $this->actingAs($customer)
            ->withSession(['locale' => 'ar'])
            ->get(route('customer.requests.show', $request))
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee(__('requests.progress_hint.scheduled', [], 'ar'))
            ->assertDontSee('requests.progress_hint.');
    }

    public function test_a_message_action_appears_only_once_a_professional_is_assigned(): void
    {
        [$customer, $request] = $this->ownedRequest(['status' => 'pending']);

        $this->actingAs($customer)->get(route('customer.requests.show', $request))
            ->assertOk()
            ->assertDontSee(route('customer.messages.show', $request), false);

        $worker = User::factory()->worker()->create();
        WorkerProfile::factory()->for($worker, 'user')->verified()->create();
        $request->update(['worker_id' => $worker->id]);

        $this->actingAs($customer)->get(route('customer.requests.show', $request))
            ->assertOk()
            ->assertSee(route('customer.messages.show', $request), false);
    }

    /**
     * The customer's preferred_date/preferred_time is legitimately shown
     * elsewhere on this same page (the "Preferred time" summary field), so
     * these regression tests inspect only the sidebar "Appointment" card's own
     * markup — a short, bounded slice right after its heading — rather than
     * the full page, to avoid a false pass/fail from that unrelated field.
     */
    private function appointmentCardFragment(string $html): string
    {
        // The exact sidebar card heading markup — not just the word
        // "Appointment", which also prefixes the unrelated timeline step
        // label "Appointment scheduled" earlier on the same page.
        $needle = '<h3 class="t-card">'.__('requests.appointment').'</h3>';
        $pos = strpos($html, $needle);
        $this->assertNotFalse($pos, 'the Appointment card heading was not found on the page');

        return substr($html, $pos, 300);
    }

    /**
     * ATTACK POINT #2A regression: the sidebar "Appointment" card must never
     * present the customer's own (unconfirmed) preferred_date/preferred_time
     * as if it were a confirmed appointment. Only a real Appointment row may
     * be shown there; otherwise the existing "not scheduled yet" copy applies.
     */
    public function test_an_accepted_request_with_no_appointment_does_not_show_the_preferred_date_as_an_appointment(): void
    {
        $worker = User::factory()->worker()->create();
        [$customer, $request] = $this->ownedRequest([
            'status' => 'accepted',
            'worker_id' => $worker->id,
            'preferred_date' => now()->addDays(2)->toDateString(),
            'preferred_time' => '09:00',
        ]);

        $html = $this->actingAs($customer)->get(route('customer.requests.show', $request))
            ->assertOk()
            ->getContent();

        // The customer's preferred slot must not appear inside the Appointment card.
        $this->assertStringNotContainsString('09:00', $this->appointmentCardFragment($html));
    }

    public function test_an_accepted_request_with_no_appointment_shows_the_not_scheduled_state(): void
    {
        $worker = User::factory()->worker()->create();
        [$customer, $request] = $this->ownedRequest(['status' => 'accepted', 'worker_id' => $worker->id]);

        $html = $this->actingAs($customer)->get(route('customer.requests.show', $request))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(__('requests.pending_schedule'), $this->appointmentCardFragment($html));
    }

    public function test_a_scheduled_request_with_a_real_appointment_shows_its_actual_date_and_time(): void
    {
        $worker = User::factory()->worker()->create();
        [$customer, $request] = $this->ownedRequest([
            'status' => 'scheduled',
            'worker_id' => $worker->id,
            // Deliberately different from the real appointment below, so a
            // match proves the card reads the Appointment, not the preference.
            'preferred_date' => now()->addDays(30)->toDateString(),
            'preferred_time' => '09:00',
        ]);
        Appointment::create([
            'service_request_id' => $request->id,
            'worker_id' => $worker->id,
            'scheduled_date' => now()->addDays(3)->toDateString(),
            'scheduled_time' => '14:00',
            'status' => 'scheduled',
        ]);

        $html = $this->actingAs($customer)->get(route('customer.requests.show', $request))
            ->assertOk()
            ->getContent();
        $fragment = $this->appointmentCardFragment($html);

        $this->assertStringContainsString('14:00', $fragment);
        $this->assertStringNotContainsString('09:00', $fragment);
        $this->assertStringNotContainsString(__('requests.pending_schedule'), $fragment);
    }
}
