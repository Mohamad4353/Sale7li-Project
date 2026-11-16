<?php

namespace Tests\Feature\Customer;

use App\Models\Address;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Phase 27A — the session-backed booking draft. It lets a customer step away to
 * add an address without losing what they typed. Strictly non-authoritative:
 * StoreBookingRequest still re-validates every field on the real submit.
 */
class BookingDraftTest extends TestCase
{
    use RefreshDatabase;

    private function customer(): User
    {
        return User::factory()->customer()->create();
    }

    public function test_draft_endpoint_persists_whitelisted_fields_and_redirects_to_addresses(): void
    {
        $customer = $this->customer();
        $cat = ServiceCategory::factory()->create(['is_active' => true]);
        $address = Address::factory()->for($customer, 'user')->create();

        $this->actingAs($customer)->post(route('customer.booking.draft'), [
            'service_category_id' => $cat->id,
            'problem_description' => 'The boiler is making a loud noise.',
            'is_emergency' => '1',
            'address_id' => $address->id,
            'preferred_date' => now()->addDays(3)->toDateString(),
            'preferred_time' => '11:30',
            'next' => 'addresses',
        ])->assertRedirect(route('customer.addresses.index'));

        $draft = session('booking.draft');
        $this->assertEqualsCanonicalizing(
            ['service_category_id', 'problem_description', 'is_emergency', 'address_id', 'preferred_date', 'preferred_time'],
            array_keys($draft),
        );
        $this->assertEquals($cat->id, $draft['service_category_id']);
        $this->assertSame('The boiler is making a loud noise.', $draft['problem_description']);
        $this->assertSame('11:30', $draft['preferred_time']);
    }

    public function test_draft_is_rehydrated_onto_the_booking_form(): void
    {
        $customer = $this->customer();
        $cat = ServiceCategory::factory()->create(['is_active' => true, 'name' => 'Plumbing']);
        $address = Address::factory()->for($customer, 'user')->create(['title' => 'Home base']);

        $this->actingAs($customer)->post(route('customer.booking.draft'), [
            'service_category_id' => $cat->id,
            'problem_description' => 'DRAFT_DESCRIPTION_MARKER',
            'is_emergency' => '1',
            'address_id' => $address->id,
            'preferred_date' => '2026-07-20',
            'preferred_time' => '14:15',
            'next' => 'book',
        ])->assertRedirect(route('customer.booking.create'));

        $this->actingAs($customer)->get(route('customer.booking.create'))
            ->assertOk()
            ->assertSee('DRAFT_DESCRIPTION_MARKER')
            ->assertSee('value="'.$cat->id.'" checked', false)
            ->assertSee('value="'.$address->id.'" selected', false)
            ->assertSee('name="is_emergency" value="1" checked', false)
            ->assertSee('value="2026-07-20"', false)
            ->assertSee('value="14:15"', false);
    }

    public function test_draft_ignores_non_whitelisted_and_financial_fields(): void
    {
        $customer = $this->customer();
        $cat = ServiceCategory::factory()->create(['is_active' => true]);

        $this->actingAs($customer)->post(route('customer.booking.draft'), [
            'service_category_id' => $cat->id,
            'is_emergency' => '1',
            'status' => 'completed',
            'worker_id' => 999,
            'quoted_total' => 5000,
            'final_total' => 5000,
            'payment_status' => 'paid',
            'reference' => 'SL-HACKED',
            'customer_id' => 1,
        ]);

        $draft = session('booking.draft');
        $this->assertEqualsCanonicalizing(['service_category_id', 'is_emergency'], array_keys($draft));
        foreach (['status', 'worker_id', 'quoted_total', 'final_total', 'payment_status', 'reference', 'customer_id'] as $forbidden) {
            $this->assertArrayNotHasKey($forbidden, $draft);
        }
    }

    public function test_array_injection_into_a_whitelisted_key_is_dropped(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer)->post(route('customer.booking.draft'), [
            'service_category_id' => ['nested' => 'x'],
            'problem_description' => 'still fine',
        ]);

        $draft = session('booking.draft');
        $this->assertArrayNotHasKey('service_category_id', $draft);
        $this->assertSame(['problem_description' => 'still fine'], $draft);
    }

    public function test_photos_never_enter_the_draft(): void
    {
        $customer = $this->customer();
        $cat = ServiceCategory::factory()->create(['is_active' => true]);

        $this->actingAs($customer)->post(route('customer.booking.draft'), [
            'service_category_id' => $cat->id,
            'photos' => [UploadedFile::fake()->create('x.jpg', 10)],
        ]);

        $draft = session('booking.draft');
        $this->assertArrayNotHasKey('photos', $draft);
        $this->assertSame(['service_category_id'], array_keys($draft));
    }

    public function test_next_target_is_strictly_allowlisted(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer)->post(route('customer.booking.draft'), ['next' => 'https://evil.example/phish'])
            ->assertRedirect(route('customer.booking.create'));

        $this->actingAs($customer)->post(route('customer.booking.draft'), ['next' => '//evil.example'])
            ->assertRedirect(route('customer.booking.create'));

        $this->actingAs($customer)->post(route('customer.booking.draft'), ['next' => 'addresses'])
            ->assertRedirect(route('customer.addresses.index'));

        $this->actingAs($customer)->post(route('customer.booking.draft'), [])
            ->assertRedirect(route('customer.booking.create'));
    }

    public function test_successful_booking_clears_the_draft(): void
    {
        $customer = $this->customer();
        $cat = ServiceCategory::factory()->create(['is_active' => true]);
        $address = Address::factory()->for($customer, 'user')->create();

        $this->actingAs($customer)->post(route('customer.booking.draft'), [
            'service_category_id' => $cat->id,
            'problem_description' => 'stale draft',
            'next' => 'book',
        ]);
        $this->assertNotNull(session('booking.draft'));

        $this->actingAs($customer)->post(route('customer.booking.store'), [
            'service_category_id' => $cat->id,
            'address_id' => $address->id,
            'problem_description' => 'Real submission.',
            'preferred_date' => now()->addDays(2)->toDateString(),
            'preferred_time' => '10:00',
        ])->assertRedirect();

        $this->assertNull(session('booking.draft'));
        $this->assertSame(1, ServiceRequest::count());
    }

    public function test_draft_endpoint_rejects_guests_workers_and_unverified_customers(): void
    {
        $this->post(route('customer.booking.draft'), [])->assertRedirect(route('login'));

        $this->actingAs(User::factory()->worker()->create())
            ->post(route('customer.booking.draft'), [])->assertForbidden();

        $unverified = User::factory()->customer()->create(['email_verified_at' => null]);
        $this->actingAs($unverified)->post(route('customer.booking.draft'), [])
            ->assertRedirect(route('verification.notice'));
    }

    public function test_rebook_still_prefills_from_the_past_request_even_with_a_draft_present(): void
    {
        $customer = $this->customer();
        $cat = ServiceCategory::factory()->create(['is_active' => true, 'name' => 'Appliance Repair']);
        $address = Address::factory()->for($customer, 'user')->create();
        $past = ServiceRequest::factory()->for($customer, 'customer')->status('completed')->create([
            'service_category_id' => $cat->id,
            'address_id' => $address->id,
            'problem_description' => 'PAST_REQUEST_DESCRIPTION',
            'is_emergency' => true,
        ]);

        $this->actingAs($customer)->post(route('customer.booking.draft'), [
            'problem_description' => 'UNRELATED_DRAFT_TEXT',
            'next' => 'book',
        ]);

        $this->actingAs($customer)->get(route('customer.requests.rebook', $past))
            ->assertOk()
            ->assertSee('PAST_REQUEST_DESCRIPTION')
            ->assertDontSee('UNRELATED_DRAFT_TEXT');
    }

    public function test_address_ownership_is_still_enforced_on_submit_despite_a_drafted_id(): void
    {
        $customer = $this->customer();
        $cat = ServiceCategory::factory()->create(['is_active' => true]);
        $foreignAddress = Address::factory()->for(User::factory()->customer()->create(), 'user')->create();

        $this->actingAs($customer)->post(route('customer.booking.store'), [
            'service_category_id' => $cat->id,
            'address_id' => $foreignAddress->id,
            'problem_description' => 'Trying to use someone else\'s address.',
            'preferred_date' => now()->addDays(2)->toDateString(),
            'preferred_time' => '10:00',
        ])->assertSessionHasErrors('address_id');

        $this->assertSame(0, ServiceRequest::count());
    }

    public function test_the_submit_ignores_injected_ownership_and_financial_fields(): void
    {
        $customer = $this->customer();
        $victim = User::factory()->customer()->create();
        $someWorker = User::factory()->worker()->create();
        $cat = ServiceCategory::factory()->create(['is_active' => true]);
        $address = Address::factory()->for($customer, 'user')->create();

        $this->actingAs($customer)->post(route('customer.booking.store'), [
            'service_category_id' => $cat->id,
            'address_id' => $address->id,
            'problem_description' => 'Legitimate request with a poisoned payload.',
            'preferred_date' => now()->addDays(2)->toDateString(),
            'preferred_time' => '10:00',
            // None of the below are in StoreBookingRequest::rules(), and store()
            // builds an explicit attribute array — they must be dropped entirely.
            'customer_id' => $victim->id,
            'worker_id' => $someWorker->id,
            'status' => 'completed',
            'payment_status' => 'paid',
            'quoted_total' => 9999,
            'final_total' => 9999,
            'reference' => 'SL-HACKED1',
        ])->assertRedirect();

        $request = ServiceRequest::sole();
        $this->assertSame($customer->id, $request->customer_id);   // the actor, never the injected id
        $this->assertNull($request->worker_id);                     // assignment is an ops decision
        $this->assertSame('pending', $request->status);
        $this->assertSame('pending', $request->payment_status);
        $this->assertNull($request->quoted_total);
        $this->assertNull($request->final_total);
        $this->assertNotSame('SL-HACKED1', $request->reference);    // server-generated
    }

    public function test_the_submit_rejects_an_inactive_or_unknown_service_category(): void
    {
        $customer = $this->customer();
        $address = Address::factory()->for($customer, 'user')->create();
        $inactive = ServiceCategory::factory()->create(['is_active' => false]);

        $base = [
            'address_id' => $address->id,
            'problem_description' => 'Category should not be accepted.',
            'preferred_date' => now()->addDays(2)->toDateString(),
            'preferred_time' => '10:00',
        ];

        $this->actingAs($customer)->from(route('customer.booking.create'))
            ->post(route('customer.booking.store'), [...$base, 'service_category_id' => $inactive->id])
            ->assertSessionHasErrors('service_category_id');

        $this->actingAs($customer)->from(route('customer.booking.create'))
            ->post(route('customer.booking.store'), [...$base, 'service_category_id' => 999999])
            ->assertSessionHasErrors('service_category_id');

        $this->assertSame(0, ServiceRequest::count());
    }

    public function test_the_booking_routes_keep_their_throttle_and_verified_guards(): void
    {
        $mw = fn (string $name) => collect(app('router')->getRoutes()->getByName($name)->gatherMiddleware());

        $this->assertTrue($mw('customer.booking.store')->contains('throttle:booking'));
        $this->assertTrue($mw('customer.booking.store')->contains('verified'));
        $this->assertTrue($mw('customer.booking.availability')->contains('verified'));
        $this->assertTrue($mw('customer.booking.availability')->contains('throttle:60,1'));
    }
}
