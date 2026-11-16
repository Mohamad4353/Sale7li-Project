<?php

namespace Tests\Feature\Unit;

use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\WorkerAvailability;
use App\Models\WorkerProfile;
use App\Models\WorkerService;
use App\Support\BookingAvailability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Phase 28 — edges of the advisory coverage calc not already pinned by
 * BookingAvailabilityCheckTest: count > 1, the covered flag with zero workers,
 * a qualified worker whose only open day is a different weekday, and time
 * clearly inside vs. clearly outside the published window.
 *
 * Exact window-boundary minutes are deliberately NOT asserted — that comparison
 * is storage-format dependent and not a stable cross-environment contract.
 */
class BookingAvailabilityContractTest extends TestCase
{
    use RefreshDatabase;

    private function qualifiedWorker(ServiceCategory $cat, int $weekday, ?string $start = '09:00', ?string $end = '17:00'): User
    {
        $worker = User::factory()->worker()->create();
        WorkerProfile::factory()->for($worker, 'user')->verified()->create();
        WorkerService::create([
            'worker_id' => $worker->id, 'service_category_id' => $cat->id, 'price_from' => 40, 'is_active' => true,
        ]);
        WorkerAvailability::factory()->create([
            'worker_id' => $worker->id, 'day_of_week' => $weekday, 'is_available' => true,
            'start_time' => $start, 'end_time' => $end,
        ]);

        return $worker;
    }

    public function test_count_reflects_every_qualified_available_worker(): void
    {
        $cat = ServiceCategory::factory()->create(['is_active' => true]);
        $date = Carbon::parse('2026-06-15');   // Monday -> schema day_of_week 0
        $this->qualifiedWorker($cat, 0);
        $this->qualifiedWorker($cat, 0);
        $this->qualifiedWorker($cat, 0);

        $result = BookingAvailability::coverage($cat->id, $date);

        $this->assertSame(3, $result['count']);
        $this->assertTrue($result['covered']);
    }

    public function test_covered_is_false_with_no_qualified_workers(): void
    {
        $cat = ServiceCategory::factory()->create(['is_active' => true]);

        $result = BookingAvailability::coverage($cat->id, Carbon::parse('2026-06-15'));

        $this->assertSame(0, $result['count']);
        $this->assertFalse($result['covered']);
    }

    public function test_a_worker_open_only_on_another_weekday_is_not_counted(): void
    {
        $cat = ServiceCategory::factory()->create(['is_active' => true]);
        $this->qualifiedWorker($cat, 2);   // open Wednesday only

        $this->assertSame(0, BookingAvailability::coverage($cat->id, Carbon::parse('2026-06-15'))['count']);
    }

    public function test_time_clearly_inside_vs_clearly_outside_the_published_window(): void
    {
        $cat = ServiceCategory::factory()->create(['is_active' => true]);
        $date = Carbon::parse('2026-06-15');
        $this->qualifiedWorker($cat, 0, '09:00', '17:00');

        $this->assertSame(1, BookingAvailability::coverage($cat->id, $date, '12:30')['count']);
        $this->assertSame(0, BookingAvailability::coverage($cat->id, $date, '07:00')['count']);
        $this->assertSame(0, BookingAvailability::coverage($cat->id, $date, '21:00')['count']);
        $this->assertSame(1, BookingAvailability::coverage($cat->id, $date, null)['count'], 'no time = day-level check only');
    }
}
