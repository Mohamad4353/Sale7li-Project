<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\WorkerProfile;
use App\Models\WorkerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Features 3, 4, 9 already existed in SALE7LI. These lock in the behaviour the
 * brief asks for (and the one real bug fixed along the way: the "verified"
 * badge was rendering unconditionally on the service page).
 */
class ExistingFeaturesVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function visibleWorker(string $verification = 'verified'): User
    {
        $worker = User::factory()->worker()->create();
        WorkerProfile::factory()->for($worker, 'user')->create(['verification_status' => $verification]);
        WorkerService::create([
            'worker_id' => $worker->id,
            'service_category_id' => ServiceCategory::factory()->create()->id,
            'price_from' => 40, 'is_active' => true,
        ]);

        return $worker;
    }

    // ---- Feature 3: service-request status tracking --------------------------

    public function test_customer_request_page_shows_the_visual_status_timeline(): void
    {
        $customer = User::factory()->customer()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('in_progress')->create();

        $this->actingAs($customer)->get(route('customer.requests.show', $request))
            ->assertOk()
            ->assertSee(__('requests.timeline'))
            ->assertSee(__('requests.steps.in_progress'))
            ->assertSee(__('status.request.in_progress'));
    }

    public function test_cancelled_request_timeline_shows_the_terminal_state(): void
    {
        $customer = User::factory()->customer()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('cancelled')->create();

        $this->actingAs($customer)->get(route('customer.requests.show', $request))
            ->assertOk()
            ->assertSee(__('status.request.cancelled'));
    }

    // ---- Feature 4: worker verification ------------------------------------

    public function test_verified_badge_shows_only_for_verified_professionals(): void
    {
        $verified = $this->visibleWorker('verified');

        $this->get(route('workers.show', $verified))
            ->assertOk()
            ->assertSee(__('workers.verified'));

        // A pending profile is not marketplace-visible at all (404), so it can
        // never render a "verified" badge.
        $pending = $this->visibleWorker('pending');
        $this->get(route('workers.show', $pending))->assertNotFound();
    }

    public function test_service_page_does_not_claim_unverified_workers_are_verified(): void
    {
        // The service page filters to verified professionals already; this locks
        // in the fixed conditional so an unverified row could never show a badge.
        $category = ServiceCategory::factory()->create();
        $worker = User::factory()->worker()->create();
        WorkerProfile::factory()->for($worker, 'user')->create(['verification_status' => 'verified']);
        WorkerService::create(['worker_id' => $worker->id, 'service_category_id' => $category->id, 'price_from' => 40, 'is_active' => true]);

        $html = $this->get(route('services.show', $category))->assertOk()->getContent();
        $shieldCount = substr_count($html, __('status.verification.verified'));
        $this->assertSame(1, $shieldCount, 'exactly one verified professional, one badge');
    }

    public function test_admin_can_change_a_workers_verification_status(): void
    {
        $worker = User::factory()->worker()->create();
        $profile = WorkerProfile::factory()->for($worker, 'user')->create(['verification_status' => 'pending']);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.workers.verification', $profile), ['verification_status' => 'verified'])
            ->assertRedirect();

        $this->assertSame('verified', $profile->fresh()->verification_status);
    }

    // ---- Feature 9: photo attachments on a service problem ----------------

    public function test_customer_can_attach_photos_when_describing_a_problem(): void
    {
        $customer = User::factory()->customer()->create();
        $category = ServiceCategory::factory()->create();
        $address = Address::factory()->for($customer, 'user')->create();

        $this->actingAs($customer)->post(route('customer.booking.store'), [
            'service_category_id' => $category->id,
            'address_id' => $address->id,
            'problem_description' => 'Cracked pipe under the sink, photos attached.',
            'preferred_date' => now()->addDay()->toDateString(),
            'preferred_time' => '10:00',
            'photos' => [new UploadedFile(base_path('tests/Fixtures/pixel.png'), 'leak.png', 'image/png', null, true)],
        ])->assertRedirect();

        $request = ServiceRequest::where('customer_id', $customer->id)->firstOrFail();
        $this->assertNotEmpty($request->problem_images);
        $this->assertStringStartsWith('requests/', $request->problem_images[0]);
    }

    public function test_a_disguised_executable_photo_is_rejected(): void
    {
        $customer = User::factory()->customer()->create();
        $category = ServiceCategory::factory()->create();
        $address = Address::factory()->for($customer, 'user')->create();

        $this->actingAs($customer)->from(route('customer.booking.create'))->post(route('customer.booking.store'), [
            'service_category_id' => $category->id,
            'address_id' => $address->id,
            'problem_description' => 'Trying to sneak a script in.',
            'preferred_date' => now()->addDay()->toDateString(),
            'preferred_time' => '10:00',
            'photos' => [UploadedFile::fake()->create('payload.php', 12, 'application/x-httpd-php')],
        ])->assertSessionHasErrors('photos.0');

        $this->assertDatabaseCount('service_requests', 0);
    }
}
