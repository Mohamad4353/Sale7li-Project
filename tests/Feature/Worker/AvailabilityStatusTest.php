<?php

namespace Tests\Feature\Worker;

use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\WorkerAvailability;
use App\Models\WorkerProfile;
use App\Models\WorkerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature 5 — a coarse "Available today / Available / Busy / Unavailable" hint
 * derived from the existing weekly worker_availability rows. Display only; the
 * editor and its data are untouched.
 */
class AvailabilityStatusTest extends TestCase
{
    use RefreshDatabase;

    private function profile(): WorkerProfile
    {
        $worker = User::factory()->worker()->create();

        return WorkerProfile::factory()->for($worker, 'user')->create(['verification_status' => 'verified']);
    }

    private function slot(WorkerProfile $p, int $day, string $start, string $end, bool $on = true): void
    {
        WorkerAvailability::create([
            'worker_id' => $p->user_id,
            'day_of_week' => $day,
            'start_time' => $start,
            'end_time' => $end,
            'is_available' => $on,
        ]);
    }

    public function test_no_published_availability_reads_as_unavailable(): void
    {
        $this->assertSame('unavailable', $this->profile()->availabilityStatus());
    }

    public function test_a_slot_covering_now_reads_as_available_today(): void
    {
        $p = $this->profile();
        $this->slot($p, now()->dayOfWeekIso - 1, '00:00:00', '23:59:59');

        $this->assertSame('available_today', $p->fresh()->availabilityStatus());
    }

    public function test_published_but_not_right_now_reads_as_available(): void
    {
        $p = $this->profile();
        // A slot on a different weekday only.
        $this->slot($p, (now()->dayOfWeekIso) % 7, '09:00:00', '10:00:00');

        $this->assertSame('available', $p->fresh()->availabilityStatus());
    }

    public function test_a_disabled_row_does_not_count_as_published(): void
    {
        $p = $this->profile();
        $this->slot($p, now()->dayOfWeekIso - 1, '00:00:00', '23:59:59', on: false);

        $this->assertSame('unavailable', $p->fresh()->availabilityStatus());
    }

    public function test_the_busy_flag_wins_over_the_schedule(): void
    {
        $p = $this->profile();
        $this->slot($p, now()->dayOfWeekIso - 1, '00:00:00', '23:59:59');

        $this->assertSame('busy', $p->fresh()->availabilityStatus(busy: true));
    }

    public function test_the_badge_renders_on_a_worker_profile(): void
    {
        $p = $this->profile();
        WorkerService::create([
            'worker_id' => $p->user_id,
            'service_category_id' => ServiceCategory::factory()->create()->id,
            'price_from' => 40, 'is_active' => true,
        ]);
        $this->slot($p, now()->dayOfWeekIso - 1, '00:00:00', '23:59:59');

        $this->get(route('workers.show', $p->user))
            ->assertOk()
            ->assertSee(__('workers.availability_status.available_today'));
    }

    public function test_the_badge_renders_in_the_discovery_list_in_arabic(): void
    {
        $p = $this->profile();
        WorkerService::create([
            'worker_id' => $p->user_id,
            'service_category_id' => ServiceCategory::factory()->create()->id,
            'price_from' => 40, 'is_active' => true,
        ]);

        $this->withSession(['locale' => 'ar'])
            ->get(route('workers.index'))
            ->assertOk()
            ->assertSee(__('workers.availability_status.unavailable', [], 'ar'));
    }
}
