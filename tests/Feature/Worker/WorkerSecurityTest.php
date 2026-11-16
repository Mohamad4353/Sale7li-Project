<?php

namespace Tests\Feature\Worker;

use App\Models\ServiceCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WorkerSecurityTest extends TestCase
{
    use InteractsWithWorkers, RefreshDatabase;

    public function test_a_worker_cannot_self_verify_or_touch_reputation_through_any_worker_endpoint(): void
    {
        $worker = $this->workerUser(verification: 'pending', profileAttrs: [
            'rating_average' => 0, 'rating_count' => 0, 'completed_jobs' => 0, 'completion_rate' => 100, 'cancellation_rate' => 0,
        ]);
        $category = ServiceCategory::factory()->create(['is_active' => true]);

        $poison = [
            'verification_status' => 'verified',
            'rating_average' => 5, 'rating_count' => 999, 'completed_jobs' => 999,
            'completion_rate' => 12, 'cancellation_rate' => 88,
            'user_id' => 999, 'id' => 999,
        ];

        $this->actingAs($worker)->put(route('worker.services.update'), [
            'services' => [$category->id => ['enabled' => '1']],
            ...$poison,
        ])->assertRedirect();

        $this->actingAs($worker)->put(route('worker.availability.update'), [
            ...$this->weekPayload([1]),
            ...$poison,
        ])->assertRedirect();

        $this->actingAs($worker)->put(route('worker.profile.update'), [
            'name' => 'W', 'experience_years' => 3, 'service_radius_km' => 10,
            ...$poison,
        ])->assertRedirect();

        $profile = $worker->workerProfile->fresh();
        $this->assertSame('pending', $profile->verification_status);
        $this->assertEqualsWithDelta(0.0, (float) $profile->rating_average, 0.001);
        $this->assertSame(0, $profile->rating_count);
        $this->assertSame(0, $profile->completed_jobs);
        $this->assertEqualsWithDelta(100.0, (float) $profile->completion_rate, 0.001);
        $this->assertEqualsWithDelta(0.0, (float) $profile->cancellation_rate, 0.001);
        $this->assertSame($worker->id, $profile->user_id);
    }

    public function test_service_updates_are_always_scoped_to_the_authenticated_worker(): void
    {
        $owner = $this->workerUser();
        $intruder = $this->workerUser();
        $category = ServiceCategory::factory()->create(['is_active' => true]);
        $ownerService = $this->activeService($owner, $category, ['price_from' => 40]);

        $this->actingAs($intruder)->put(route('worker.services.update'), [
            'worker_id' => $owner->id,
            'services' => [$category->id => ['enabled' => '1', 'price_from' => '1']],
        ])->assertRedirect();

        $this->assertDatabaseHas('worker_services', ['id' => $ownerService->id, 'worker_id' => $owner->id, 'price_from' => 40]);
        $this->assertDatabaseHas('worker_services', ['worker_id' => $intruder->id, 'service_category_id' => $category->id, 'price_from' => 1]);
    }

    public function test_availability_updates_are_always_scoped_to_the_authenticated_worker(): void
    {
        $owner = $this->workerUser();
        $intruder = $this->workerUser();
        $this->availabilityRow($owner, 0, ['start_time' => '08:00', 'end_time' => '12:00']);

        $this->actingAs($intruder)->put(route('worker.availability.update'), [
            'worker_id' => $owner->id,
            ...$this->weekPayload([0], '22:00', '23:00'),
        ])->assertRedirect();

        $ownerRow = $owner->workerProfile->availability()->where('day_of_week', 0)->firstOrFail();
        $this->assertSame('08:00', substr((string) $ownerRow->start_time, 0, 5));
        $this->assertDatabaseHas('worker_availability', ['worker_id' => $intruder->id, 'day_of_week' => 0, 'is_available' => true]);
    }

    public function test_a_hidden_workers_photo_is_not_reachable_by_anyone_else(): void
    {
        Storage::fake('local');
        $hidden = $this->workerUser(verification: 'pending');
        $this->actingAs($hidden)->put(route('worker.profile.update'), [
            'name' => 'H', 'experience_years' => 2, 'service_radius_km' => 10,
            'photo' => new UploadedFile(base_path('tests/Fixtures/pixel.png'), 'p.png', 'image/png', null, true),
        ])->assertRedirect();

        $other = $this->workerUser();
        $customer = \App\Models\User::factory()->customer()->create();

        // A genuine guest (drop the acting-as user left over from the upload).
        $this->app['auth']->forgetGuards();
        $this->get(route('workers.photo', $hidden))->assertNotFound();

        $this->actingAs($other)->get(route('workers.photo', $hidden))->assertNotFound();
        $this->actingAs($customer)->get(route('workers.photo', $hidden))->assertNotFound();
        $this->actingAs($hidden)->get(route('workers.photo', $hidden))->assertOk();
    }

    public function test_worker_write_routes_reject_guests(): void
    {
        $this->put(route('worker.services.update'), ['services' => []])->assertRedirect(route('login'));
        $this->put(route('worker.availability.update'), $this->weekPayload([1]))->assertRedirect(route('login'));
    }
}
