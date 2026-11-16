<?php

namespace Tests\Feature\Customer;

use App\Models\Appointment;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\WorkerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 35.4 — the customer dashboard's "active requests" hub. Reuses the
 * dashboard's existing eager-loaded request data; adds no analytics change and
 * no cross-customer exposure.
 */
class DashboardRequestsTest extends TestCase
{
    use RefreshDatabase;

    private function assignedWorker(): User
    {
        $worker = User::factory()->worker()->create(['name' => 'Nadia Fares']);
        WorkerProfile::factory()->for($worker, 'user')->verified()->create();

        return $worker;
    }

    public function test_an_active_request_is_surfaced_with_progress_professional_and_actions(): void
    {
        $customer = User::factory()->customer()->create();
        $worker = $this->assignedWorker();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('scheduled')->create([
            'service_category_id' => ServiceCategory::factory()->create(['name' => 'AC Repair'])->id,
            'worker_id' => $worker->id,
        ]);
        Appointment::create([
            'service_request_id' => $request->id, 'worker_id' => $worker->id,
            'scheduled_date' => now()->addDays(2)->toDateString(), 'scheduled_time' => '14:00', 'status' => 'scheduled',
        ]);

        $this->actingAs($customer)->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee(__('dashboard.stat_active_requests'))
            ->assertSee('AC Repair')
            ->assertSee($request->reference)
            ->assertSee(__('requests.progress_hint.scheduled'))
            ->assertSee('Nadia Fares')
            ->assertSee(route('customer.requests.show', $request), false)
            ->assertSee(route('customer.messages.show', $request), false);
    }

    public function test_the_active_section_is_hidden_when_nothing_is_in_flight(): void
    {
        $customer = User::factory()->customer()->create();
        ServiceRequest::factory()->for($customer, 'customer')->status('completed')->create();
        ServiceRequest::factory()->for($customer, 'customer')->status('cancelled')->create();

        $this->actingAs($customer)->get(route('customer.dashboard'))
            ->assertOk()
            ->assertDontSee(__('dashboard.stat_active_requests'));
    }

    public function test_active_requests_never_leak_across_customers(): void
    {
        $mine = User::factory()->customer()->create();
        $other = User::factory()->customer()->create();
        $theirs = ServiceRequest::factory()->for($other, 'customer')->status('in_progress')->create();

        $this->actingAs($mine)->get(route('customer.dashboard'))
            ->assertOk()
            ->assertDontSee($theirs->reference)
            ->assertDontSee(__('dashboard.stat_active_requests'));
    }

    public function test_an_outstanding_amount_due_is_surfaced_on_the_active_card(): void
    {
        $customer = User::factory()->customer()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('in_progress')->create([
            'quoted_total' => 175, 'payment_status' => 'pending',
        ]);

        $this->actingAs($customer)->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee(__('payments.amount_due'))
            ->assertSee('175');
    }

    public function test_a_message_link_appears_only_when_a_professional_is_assigned(): void
    {
        $customer = User::factory()->customer()->create();
        $unassigned = ServiceRequest::factory()->for($customer, 'customer')->status('accepted')->create();

        $this->actingAs($customer)->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee(__('requests.progress_hint.accepted'))
            ->assertDontSee(route('customer.messages.show', $unassigned), false);
    }

    public function test_the_active_hub_localises_to_arabic_without_raw_keys(): void
    {
        $customer = User::factory()->customer()->create();
        $worker = $this->assignedWorker();
        ServiceRequest::factory()->for($customer, 'customer')->status('on_the_way')->create(['worker_id' => $worker->id]);

        $this->actingAs($customer)->withSession(['locale' => 'ar'])->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee(__('requests.progress_hint.on_the_way', [], 'ar'))
            ->assertSee(__('dashboard.view_request', [], 'ar'))
            ->assertDontSee('requests.progress_hint.')
            ->assertDontSee('dashboard.view_request');
    }
}
