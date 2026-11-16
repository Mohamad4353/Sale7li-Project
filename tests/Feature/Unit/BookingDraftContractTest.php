<?php

namespace Tests\Feature\Unit;

use App\Support\BookingDraft;
use Tests\TestCase;

/**
 * Phase 28 — the session booking-draft class contract in isolation from the
 * HTTP route. The security-relevant part is the field whitelist plus the scalar
 * filter; get() also re-applies the key whitelist so a tampered session cannot
 * surface extra keys to the view.
 */
class BookingDraftContractTest extends TestCase
{
    private const SESSION_KEY = 'booking.draft';

    public function test_fields_whitelist_is_the_expected_six_keys(): void
    {
        $this->assertEqualsCanonicalizing([
            'service_category_id', 'problem_description', 'is_emergency',
            'address_id', 'preferred_date', 'preferred_time',
        ], BookingDraft::FIELDS);
    }

    public function test_put_keeps_only_whitelisted_keys(): void
    {
        BookingDraft::put([
            'service_category_id' => '5',
            'problem_description' => 'Leak under the sink',
            'is_emergency' => '1',
            'address_id' => '9',
            'preferred_date' => '2026-07-20',
            'preferred_time' => '14:15',
            'status' => 'completed',
            'worker_id' => 3,
            'quoted_total' => 999,
            'reference' => 'SL-HACK',
        ]);

        $draft = BookingDraft::get();

        $this->assertEqualsCanonicalizing(BookingDraft::FIELDS, array_keys($draft));
        $this->assertSame('Leak under the sink', $draft['problem_description']);
        foreach (['status', 'worker_id', 'quoted_total', 'reference'] as $forbidden) {
            $this->assertArrayNotHasKey($forbidden, $draft);
        }
    }

    public function test_put_drops_non_scalar_values_even_under_a_whitelisted_key(): void
    {
        BookingDraft::put([
            'service_category_id' => ['nested' => 'x'],
            'problem_description' => (object) ['a' => 1],
            'preferred_time' => '09:00',
        ]);

        $this->assertSame(['preferred_time' => '09:00'], BookingDraft::get());
    }

    public function test_put_with_no_scalar_values_stores_an_empty_draft(): void
    {
        BookingDraft::put(['service_category_id' => ['x'], 'not_allowed' => 'y']);

        $this->assertSame([], BookingDraft::get());
    }

    public function test_put_replaces_the_previous_draft_it_does_not_merge(): void
    {
        BookingDraft::put(['service_category_id' => '1', 'problem_description' => 'first']);
        BookingDraft::put(['address_id' => '7']);

        $this->assertSame(['address_id' => '7'], BookingDraft::get());
    }

    public function test_get_re_filters_a_session_poisoned_with_extra_keys(): void
    {
        session()->put(self::SESSION_KEY, [
            'service_category_id' => 2,
            'evil_flag' => 'x',
            'worker_id' => 99,
        ]);

        $this->assertSame(['service_category_id' => 2], BookingDraft::get());
    }

    public function test_get_is_empty_when_nothing_is_stored(): void
    {
        $this->assertSame([], BookingDraft::get());
    }

    public function test_forget_clears_the_draft_and_is_a_noop_when_empty(): void
    {
        BookingDraft::forget(); // nothing stored — must not error

        BookingDraft::put(['service_category_id' => '3']);
        $this->assertNotSame([], BookingDraft::get());

        BookingDraft::forget();
        $this->assertSame([], BookingDraft::get());
    }
}
