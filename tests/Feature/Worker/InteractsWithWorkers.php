<?php

namespace Tests\Feature\Worker;

use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\WorkerAvailability;
use App\Models\WorkerProfile;
use App\Models\WorkerService;

trait InteractsWithWorkers
{
    protected function workerUser(array $userAttrs = [], string $verification = 'verified', array $profileAttrs = []): User
    {
        $user = User::factory()->worker()->create($userAttrs);

        WorkerProfile::factory()->for($user, 'user')->create([
            'verification_status' => $verification,
            ...$profileAttrs,
        ]);

        return $user;
    }

    protected function activeService(User $worker, ?ServiceCategory $category = null, array $attrs = []): WorkerService
    {
        return WorkerService::create([
            'worker_id' => $worker->id,
            'service_category_id' => ($category ?? ServiceCategory::factory()->create(['is_active' => true]))->id,
            'is_active' => true,
            'price_from' => 40,
            ...$attrs,
        ]);
    }

    protected function availabilityRow(User $worker, int $day, array $attrs = []): WorkerAvailability
    {
        return WorkerAvailability::create([
            'worker_id' => $worker->id,
            'day_of_week' => $day,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'is_available' => true,
            ...$attrs,
        ]);
    }

    /** A full weekly-availability payload, days keyed 0–6. */
    protected function weekPayload(array $enabledDays, string $start = '09:00', string $end = '17:00'): array
    {
        $days = [];
        foreach (range(0, 6) as $i) {
            $days[$i] = in_array($i, $enabledDays, true)
                ? ['is_available' => '1', 'start' => $start, 'end' => $end]
                : ['is_available' => '0'];
        }

        return ['days' => $days];
    }
}
