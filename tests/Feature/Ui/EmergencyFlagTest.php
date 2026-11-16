<?php

namespace Tests\Feature\Ui;

use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Worker\InteractsWithWorkers;
use Tests\TestCase;

/**
 * P14-20 — one unified <x-emergency-flag> across the worker job list, worker
 * job detail, the admin request queue and the customer request detail. The
 * emergency business logic (`is_emergency`) is untouched.
 */
class EmergencyFlagTest extends TestCase
{
    use InteractsWithWorkers;
    use RefreshDatabase;

    private function request(bool $emergency, ?User $worker = null): ServiceRequest
    {
        return ServiceRequest::factory()
            ->for(User::factory()->customer()->create(), 'customer')
            ->status('accepted')
            ->create([
                'service_category_id' => ServiceCategory::factory()->create()->id,
                'worker_id' => $worker?->id,
                'is_emergency' => $emergency,
            ]);
    }

    public function test_worker_job_list_flags_emergency_requests_only(): void
    {
        $worker = User::factory()->worker()->create();
        $urgent = $this->request(true, $worker);

        $this->actingAs($worker)->get(route('worker.jobs.index'))->assertOk()
            ->assertSee(__('requests.emergency'));
    }

    public function test_worker_job_list_has_no_flag_without_emergencies(): void
    {
        $worker = User::factory()->worker()->create();
        $this->request(false, $worker);

        $this->actingAs($worker)->get(route('worker.jobs.index'))->assertOk()
            ->assertDontSee(__('requests.emergency'));
    }

    public function test_worker_job_detail_shows_the_emergency_banner(): void
    {
        $worker = User::factory()->worker()->create();
        $urgent = $this->request(true, $worker);

        $this->actingAs($worker)->get(route('worker.jobs.show', $urgent))->assertOk()
            ->assertSee(__('requests.emergency'))
            ->assertSee(__('requests.emergency_note'));
    }

    public function test_customer_request_detail_shows_the_emergency_banner(): void
    {
        $customer = User::factory()->customer()->create();
        $urgent = ServiceRequest::factory()->for($customer, 'customer')->status('pending')->create([
            'service_category_id' => ServiceCategory::factory()->create()->id,
            'is_emergency' => true,
        ]);

        $this->actingAs($customer)->get(route('customer.requests.show', $urgent))->assertOk()
            ->assertSee(__('requests.emergency'));
    }

    public function test_customer_request_detail_hides_the_flag_when_not_emergency(): void
    {
        $customer = User::factory()->customer()->create();
        $normal = ServiceRequest::factory()->for($customer, 'customer')->status('pending')->create([
            'service_category_id' => ServiceCategory::factory()->create()->id,
            'is_emergency' => false,
        ]);

        $this->actingAs($customer)->get(route('customer.requests.show', $normal))->assertOk()
            ->assertDontSee(__('requests.emergency'));
    }

    public function test_admin_request_queue_flags_emergencies(): void
    {
        $admin = User::factory()->admin()->create();
        $urgent = $this->request(true);

        $this->actingAs($admin)->get(route('admin.requests.index'))->assertOk()
            ->assertSee(__('requests.emergency'));
    }

    public function test_emergency_flag_is_localized_in_arabic(): void
    {
        $worker = User::factory()->worker()->create();
        $this->request(true, $worker);

        $this->actingAs($worker)->withSession(['locale' => 'ar'])
            ->get(route('worker.jobs.index'))->assertOk()
            ->assertSee(__('requests.emergency', [], 'ar'))
            ->assertDontSee(__('requests.emergency', [], 'en'));
    }
}
