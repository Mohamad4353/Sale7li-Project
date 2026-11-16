<?php

namespace Tests\Feature\Customer;

use App\Models\Address;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\WorkerAvailability;
use App\Models\WorkerProfile;
use App\Models\WorkerService;
use App\Support\BookingAvailability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Phase 27B — advisory professional-coverage during booking. It counts verified,
 * active, category-qualified professionals whose recurring weekly availability
 * covers the chosen day (and time window). It NEVER blocks a booking; the only
 * authoritative gate added is the future/≤90-day datetime rule in
 * StoreBookingRequest.
 */
class BookingAvailabilityCheckTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function category(): ServiceCategory
    {
        return ServiceCategory::factory()->create(['is_active' => true]);
    }

    /** A verified, active professional offering $category, available on $weekday (0 = Mon .. 6 = Sun). */
    private function availableWorker(ServiceCategory $category, int $weekday, array $availability = []): User
    {
        $worker = User::factory()->worker()->create();
        WorkerProfile::factory()->for($worker, 'user')->verified()->create();
        WorkerService::create([
            'worker_id' => $worker->id,
            'service_category_id' => $category->id,
            'price_from' => 40,
            'is_active' => true,
        ]);
        WorkerAvailability::factory()->create(array_merge([
            'worker_id' => $worker->id,
            'day_of_week' => $weekday,
            'is_available' => true,
            'start_time' => '09:00',
            'end_time' => '17:00',
        ], $availability));

        return $worker;
    }

    private function verifiedCustomer(): User
    {
        return User::factory()->customer()->create();
    }

    /** @return array{0:int,1:int} [categoryId, addressId] */
    private function bookingPrereqs(User $customer): array
    {
        $cat = ServiceCategory::factory()->create(['is_active' => true]);
        $address = Address::factory()->for($customer, 'user')->create();

        return [$cat->id, $address->id];
    }

    /* -------------------- coverage calculation -------------------- */

    public function test_coverage_counts_only_active_verified_qualified_available_workers(): void
    {
        $cat = $this->category();
        $other = ServiceCategory::factory()->create(['is_active' => true]);
        $date = Carbon::parse('2026-06-15');            // a Monday
        $weekday = $date->dayOfWeekIso - 1;

        // counts
        $this->availableWorker($cat, $weekday);

        // pending verification -> excluded
        $pending = User::factory()->worker()->create();
        WorkerProfile::factory()->for($pending, 'user')->create(['verification_status' => 'pending']);
        WorkerService::create(['worker_id' => $pending->id, 'service_category_id' => $cat->id, 'price_from' => 1, 'is_active' => true]);
        WorkerAvailability::factory()->create(['worker_id' => $pending->id, 'day_of_week' => $weekday, 'is_available' => true]);

        // suspended account -> excluded
        $this->availableWorker($cat, $weekday)->update(['status' => 'suspended']);

        // wrong category -> excluded
        $this->availableWorker($other, $weekday);

        // not available that weekday -> excluded
        $this->availableWorker($cat, ($weekday + 3) % 7);

        // that day's row disabled -> excluded
        $this->availableWorker($cat, $weekday, ['is_available' => false, 'start_time' => null, 'end_time' => null]);

        // inactive worker_service -> excluded
        $inactive = $this->availableWorker($cat, $weekday);
        WorkerService::where('worker_id', $inactive->id)->update(['is_active' => false]);

        $result = BookingAvailability::coverage($cat->id, $date);

        $this->assertSame(1, $result['count']);
        $this->assertTrue($result['covered']);
    }

    public function test_weekday_mapping_is_monday_zero(): void
    {
        $cat = $this->category();
        $date = Carbon::parse('2026-06-15');
        $this->assertSame(1, $date->dayOfWeekIso, 'guard: 2026-06-15 is a Monday');

        $this->availableWorker($cat, 0);   // Monday row -> should count
        $this->availableWorker($cat, 6);   // Sunday row -> should not

        $this->assertSame(1, BookingAvailability::coverage($cat->id, $date)['count']);
    }

    public function test_time_window_is_respected_when_a_time_is_supplied(): void
    {
        $cat = $this->category();
        $date = Carbon::parse('2026-06-15');
        $weekday = $date->dayOfWeekIso - 1;
        $this->availableWorker($cat, $weekday, ['start_time' => '09:00', 'end_time' => '12:00']);

        $this->assertSame(1, BookingAvailability::coverage($cat->id, $date, '10:00')['count']);
        $this->assertSame(0, BookingAvailability::coverage($cat->id, $date, '15:00')['count']);
        $this->assertSame(1, BookingAvailability::coverage($cat->id, $date, null)['count']);
    }

    public function test_a_row_with_no_window_counts_at_any_time(): void
    {
        $cat = $this->category();
        $date = Carbon::parse('2026-06-15');
        $weekday = $date->dayOfWeekIso - 1;
        $this->availableWorker($cat, $weekday, ['start_time' => null, 'end_time' => null]);

        $this->assertSame(1, BookingAvailability::coverage($cat->id, $date, '23:00')['count']);
    }

    public function test_disabled_availability_does_not_count(): void
    {
        $cat = $this->category();
        $date = Carbon::parse('2026-06-15');
        $weekday = $date->dayOfWeekIso - 1;
        $this->availableWorker($cat, $weekday, ['is_available' => false]);

        $result = BookingAvailability::coverage($cat->id, $date);
        $this->assertSame(0, $result['count']);
        $this->assertFalse($result['covered']);
    }

    /* -------------------- JSON endpoint -------------------- */

    public function test_endpoint_returns_only_count_and_covered(): void
    {
        $cat = $this->category();
        $date = Carbon::now()->addDays(7);   // in-range, same weekday as today
        $this->availableWorker($cat, $date->dayOfWeekIso - 1);

        $this->actingAs($this->verifiedCustomer())
            ->getJson(route('customer.booking.availability', ['category' => $cat->id, 'date' => $date->toDateString()]))
            ->assertOk()
            ->assertExactJson(['count' => 1, 'covered' => true]);
    }

    public function test_endpoint_validates_input(): void
    {
        $customer = $this->verifiedCustomer();
        $cat = $this->category();

        $this->actingAs($customer)->getJson(route('customer.booking.availability'))->assertStatus(422);
        $this->actingAs($customer)->getJson(route('customer.booking.availability', ['category' => 999999, 'date' => now()->addDay()->toDateString()]))->assertStatus(422);
        $this->actingAs($customer)->getJson(route('customer.booking.availability', ['category' => $cat->id, 'date' => 'not-a-date']))->assertStatus(422);
        $this->actingAs($customer)->getJson(route('customer.booking.availability', ['category' => $cat->id, 'date' => now()->subDay()->toDateString()]))->assertStatus(422);
        $this->actingAs($customer)->getJson(route('customer.booking.availability', ['category' => $cat->id, 'date' => now()->addDays(120)->toDateString()]))->assertStatus(422);
    }

    public function test_endpoint_requires_customer_and_verified_email(): void
    {
        $cat = $this->category();
        $params = ['category' => $cat->id, 'date' => now()->addDay()->toDateString()];

        $this->getJson(route('customer.booking.availability', $params))->assertUnauthorized();

        $this->actingAs(User::factory()->worker()->create())
            ->getJson(route('customer.booking.availability', $params))->assertForbidden();

        $unverified = User::factory()->customer()->create(['email_verified_at' => null]);
        $this->actingAs($unverified)
            ->get(route('customer.booking.availability', $params))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_endpoint_is_rate_limited(): void
    {
        $cat = $this->category();
        $customer = $this->verifiedCustomer();
        $params = ['category' => $cat->id, 'date' => now()->addDay()->toDateString()];

        for ($i = 0; $i < 60; $i++) {
            $this->actingAs($customer)->getJson(route('customer.booking.availability', $params))->assertOk();
        }
        $this->actingAs($customer)->getJson(route('customer.booking.availability', $params))->assertStatus(429);
    }

    /* -------------------- submit-time datetime hardening -------------------- */

    public function test_past_datetime_is_rejected_on_submit(): void
    {
        Carbon::setTestNow('2026-06-15 14:00:00');
        $customer = $this->verifiedCustomer();
        [$catId, $addressId] = $this->bookingPrereqs($customer);

        $this->actingAs($customer)->from(route('customer.booking.create'))->post(route('customer.booking.store'), [
            'service_category_id' => $catId,
            'address_id' => $addressId,
            'problem_description' => 'Leak under the sink.',
            'preferred_date' => '2026-06-15',
            'preferred_time' => '09:00',
        ])->assertSessionHasErrors('preferred_time');

        $this->assertSame(0, ServiceRequest::count());
    }

    public function test_a_future_time_later_today_is_accepted(): void
    {
        Carbon::setTestNow('2026-06-15 08:00:00');
        $customer = $this->verifiedCustomer();
        [$catId, $addressId] = $this->bookingPrereqs($customer);

        $this->actingAs($customer)->post(route('customer.booking.store'), [
            'service_category_id' => $catId,
            'address_id' => $addressId,
            'problem_description' => 'Leak under the sink.',
            'preferred_date' => '2026-06-15',
            'preferred_time' => '18:00',
        ])->assertRedirect();

        $this->assertSame(1, ServiceRequest::count());
    }

    public function test_more_than_90_days_ahead_is_rejected(): void
    {
        Carbon::setTestNow('2026-06-15 10:00:00');
        $customer = $this->verifiedCustomer();
        [$catId, $addressId] = $this->bookingPrereqs($customer);

        $payload = fn (string $date) => [
            'service_category_id' => $catId,
            'address_id' => $addressId,
            'problem_description' => 'Leak under the sink.',
            'preferred_date' => $date,
            'preferred_time' => '10:00',
        ];

        $this->actingAs($customer)->from(route('customer.booking.create'))
            ->post(route('customer.booking.store'), $payload(Carbon::now()->addDays(91)->toDateString()))
            ->assertSessionHasErrors('preferred_date');
        $this->assertSame(0, ServiceRequest::count());

        $this->actingAs($customer)
            ->post(route('customer.booking.store'), $payload(Carbon::now()->addDays(80)->toDateString()))
            ->assertRedirect();
        $this->assertSame(1, ServiceRequest::count());
    }

    public function test_zero_coverage_still_lets_the_booking_through(): void
    {
        $customer = $this->verifiedCustomer();
        [$catId, $addressId] = $this->bookingPrereqs($customer);
        // no professionals exist at all

        $this->actingAs($customer)->post(route('customer.booking.store'), [
            'service_category_id' => $catId,
            'address_id' => $addressId,
            'problem_description' => 'Nobody is listed but I still need help.',
            'preferred_date' => now()->addDays(5)->toDateString(),
            'preferred_time' => '10:00',
        ])->assertRedirect();

        $this->assertSame(1, ServiceRequest::count());
    }

    /* -------------------- advisory hint on the page -------------------- */

    public function test_booking_page_shows_the_coverage_hint_en_and_ar(): void
    {
        $cat = $this->category();
        $customer = $this->verifiedCustomer();
        Address::factory()->for($customer, 'user')->create();

        $this->actingAs($customer)->post(route('customer.booking.draft'), [
            'service_category_id' => $cat->id,
            'preferred_date' => now()->addDays(6)->toDateString(),
            'next' => 'book',
        ]);

        $en = $this->actingAs($customer)->get(route('customer.booking.create'))->assertOk();
        $en->assertSee(__('bookings.coverage_none'));
        $en->assertDontSee('bookings.coverage');

        $ar = $this->actingAs($customer)->withSession(['locale' => 'ar'])
            ->get(route('customer.booking.create'))->assertOk();
        $ar->assertSee(__('bookings.coverage_none', [], 'ar'), false);
        $ar->assertDontSee('bookings.coverage');
    }
}
