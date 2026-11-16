<?php

namespace Tests\Feature\Support;

use App\Models\Complaint;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Audit gap-fill for the complaint workflow: nobody but an admin may drive the
 * PATCH update (the assigned worker can *view* the complaint but still cannot
 * mutate it), and the customer/worker/admin detail pages render right-to-left
 * in Arabic. No production code changes.
 */
class ComplaintAuditTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{customer: User, worker: User, complaint: Complaint} */
    private function complaint(array $overrides = []): array
    {
        $customer = User::factory()->customer()->create();
        $worker = User::factory()->worker()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('accepted')->create([
            'worker_id' => $worker->id,
        ]);
        $complaint = Complaint::create(array_merge([
            'service_request_id' => $request->id,
            'reporter_id' => $customer->id,
            'category' => 'work_quality',
            'description' => 'The tap still leaks after the visit.',
            'status' => 'open',
            'priority' => 'medium',
            'internal_notes' => 'ADMIN_ONLY_SECRET_NOTE',
        ], $overrides));

        return compact('customer', 'worker', 'complaint');
    }

    public function test_a_customer_cannot_patch_a_complaint(): void
    {
        ['customer' => $customer, 'complaint' => $complaint] = $this->complaint();

        $this->actingAs($customer)
            ->patch(route('admin.complaints.update', $complaint), [
                'status' => 'resolved',
                'internal_notes' => 'tampered',
            ])
            ->assertForbidden();

        $complaint->refresh();
        $this->assertSame('open', $complaint->status);
        $this->assertSame('ADMIN_ONLY_SECRET_NOTE', $complaint->internal_notes);
    }

    public function test_the_assigned_worker_cannot_patch_a_complaint(): void
    {
        ['worker' => $worker, 'complaint' => $complaint] = $this->complaint();

        // The worker is allowed to *view* this complaint (ComplaintPolicy::view),
        // which makes the update route the meaningful boundary to test.
        $this->actingAs($worker)->get(route('worker.complaints.show', $complaint))->assertOk();

        $this->actingAs($worker)
            ->patch(route('admin.complaints.update', $complaint), [
                'status' => 'closed',
                'internal_notes' => 'tampered',
            ])
            ->assertForbidden();

        $complaint->refresh();
        $this->assertSame('open', $complaint->status);
        $this->assertSame('ADMIN_ONLY_SECRET_NOTE', $complaint->internal_notes);
    }

    public function test_a_guest_cannot_patch_a_complaint(): void
    {
        ['complaint' => $complaint] = $this->complaint();

        $this->patch(route('admin.complaints.update', $complaint), [
            'status' => 'resolved',
            'internal_notes' => 'tampered',
        ])->assertRedirect(route('login'));

        $complaint->refresh();
        $this->assertSame('open', $complaint->status);
        $this->assertSame('ADMIN_ONLY_SECRET_NOTE', $complaint->internal_notes);
    }

    public function test_customer_complaint_detail_renders_right_to_left_in_arabic(): void
    {
        ['customer' => $customer, 'complaint' => $complaint] = $this->complaint();

        $this->actingAs($customer)->withSession(['locale' => 'ar'])
            ->get(route('customer.complaints.show', $complaint))
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee(__('status.complaint.open', [], 'ar'))
            ->assertDontSee('ADMIN_ONLY_SECRET_NOTE')
            ->assertDontSee('complaints.');
    }

    public function test_worker_complaint_detail_renders_right_to_left_in_arabic(): void
    {
        ['worker' => $worker, 'complaint' => $complaint] = $this->complaint();

        $this->actingAs($worker)->withSession(['locale' => 'ar'])
            ->get(route('worker.complaints.show', $complaint))
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee(__('status.complaint.open', [], 'ar'))
            ->assertDontSee('ADMIN_ONLY_SECRET_NOTE')
            ->assertDontSee('complaints.');
    }

    public function test_admin_complaint_detail_renders_right_to_left_in_arabic(): void
    {
        ['complaint' => $complaint] = $this->complaint();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->withSession(['locale' => 'ar'])
            ->get(route('admin.complaints.show', $complaint))
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            // The admin view is the one place internal notes are shown.
            ->assertSee('ADMIN_ONLY_SECRET_NOTE')
            ->assertDontSee('complaints.');
    }
}
