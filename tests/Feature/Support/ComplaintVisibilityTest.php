<?php

namespace Tests\Feature\Support;

use App\Models\Complaint;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 26 — customer & worker visibility of complaints. Read-only pages that
 * reuse the existing complaint schema; authorization is entirely server-side
 * (query scoping + role middleware + ComplaintPolicy::view). `internal_notes`
 * is admin-only and must never reach a customer or worker view.
 */
class ComplaintVisibilityTest extends TestCase
{
    use RefreshDatabase;

    /** A complaint on a request owned by $customer and (optionally) assigned to $worker. */
    private function complaintFor(User $customer, ?User $worker = null, array $overrides = []): Complaint
    {
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('accepted')->create([
            'worker_id' => $worker?->id,
        ]);

        return Complaint::create(array_merge([
            'service_request_id' => $request->id,
            'reporter_id' => $customer->id,
            'category' => 'work_quality',
            'description' => 'The tap still leaks after the visit.',
            'status' => 'open',
            'priority' => 'medium',
        ], $overrides));
    }

    /* ----------------------------- customer ----------------------------- */

    public function test_customer_index_lists_only_their_own_complaints(): void
    {
        $alice = User::factory()->customer()->create();
        $bob = User::factory()->customer()->create();

        $mine = $this->complaintFor($alice);
        $theirs = $this->complaintFor($bob);

        $res = $this->actingAs($alice)->get(route('customer.complaints.index'))->assertOk();
        $res->assertSee($mine->request->reference);
        $res->assertDontSee($theirs->request->reference);
    }

    public function test_customer_can_open_their_own_complaint_detail(): void
    {
        $alice = User::factory()->customer()->create();
        $c = $this->complaintFor($alice);

        $this->actingAs($alice)->get(route('customer.complaints.show', $c))
            ->assertOk()
            ->assertSee('The tap still leaks after the visit.');
    }

    public function test_customer_cannot_open_another_customers_complaint(): void
    {
        $alice = User::factory()->customer()->create();
        $bob = User::factory()->customer()->create();
        $c = $this->complaintFor($bob);

        $this->actingAs($alice)->get(route('customer.complaints.show', $c))->assertForbidden();
    }

    public function test_worker_cannot_use_the_customer_complaints_index(): void
    {
        $this->actingAs(User::factory()->worker()->create())
            ->get(route('customer.complaints.index'))->assertForbidden();
    }

    public function test_guest_is_redirected_from_customer_complaints(): void
    {
        $this->get(route('customer.complaints.index'))->assertRedirect(route('login'));
    }

    /* ------------------------------ worker ------------------------------ */

    public function test_assigned_worker_sees_complaints_on_their_jobs(): void
    {
        $customer = User::factory()->customer()->create();
        $worker = User::factory()->worker()->create();
        $c = $this->complaintFor($customer, $worker);

        $this->actingAs($worker)->get(route('worker.complaints.index'))
            ->assertOk()
            ->assertSee($c->request->reference);
    }

    public function test_worker_does_not_see_complaints_on_another_workers_job(): void
    {
        $customer = User::factory()->customer()->create();
        $mine = User::factory()->worker()->create();
        $other = User::factory()->worker()->create();
        $c = $this->complaintFor($customer, $other);

        $this->actingAs($mine)->get(route('worker.complaints.index'))
            ->assertOk()
            ->assertDontSee($c->request->reference);
    }

    public function test_assigned_worker_can_open_a_complaint_on_their_job(): void
    {
        $customer = User::factory()->customer()->create();
        $worker = User::factory()->worker()->create();
        $c = $this->complaintFor($customer, $worker);

        $this->actingAs($worker)->get(route('worker.complaints.show', $c))
            ->assertOk()
            ->assertSee('The tap still leaks after the visit.');
    }

    public function test_unrelated_worker_cannot_open_a_complaint_by_id(): void
    {
        $customer = User::factory()->customer()->create();
        $assigned = User::factory()->worker()->create();
        $intruder = User::factory()->worker()->create();
        $c = $this->complaintFor($customer, $assigned);

        $this->actingAs($intruder)->get(route('worker.complaints.show', $c))->assertForbidden();
    }

    public function test_customer_cannot_use_the_worker_complaints_index(): void
    {
        $this->actingAs(User::factory()->customer()->create())
            ->get(route('worker.complaints.index'))->assertForbidden();
    }

    public function test_guest_is_redirected_from_worker_complaints(): void
    {
        $this->get(route('worker.complaints.index'))->assertRedirect(route('login'));
    }

    /* --------------------- internal notes / leakage --------------------- */

    public function test_internal_notes_never_appear_in_customer_or_worker_views(): void
    {
        $customer = User::factory()->customer()->create();
        $worker = User::factory()->worker()->create();
        $c = $this->complaintFor($customer, $worker, ['internal_notes' => 'ADMIN_ONLY_SECRET_NOTE']);

        $this->actingAs($customer)->get(route('customer.complaints.index'))->assertOk()->assertDontSee('ADMIN_ONLY_SECRET_NOTE');
        $this->actingAs($customer)->get(route('customer.complaints.show', $c))->assertOk()->assertDontSee('ADMIN_ONLY_SECRET_NOTE');
        $this->actingAs($worker)->get(route('worker.complaints.index'))->assertOk()->assertDontSee('ADMIN_ONLY_SECRET_NOTE');
        $this->actingAs($worker)->get(route('worker.complaints.show', $c))->assertOk()->assertDontSee('ADMIN_ONLY_SECRET_NOTE');
    }

    /* --------------------------- localization -------------------------- */

    public function test_complaint_pages_have_no_raw_translation_keys_en_and_ar(): void
    {
        $customer = User::factory()->customer()->create();
        $worker = User::factory()->worker()->create();
        $c = $this->complaintFor($customer, $worker);

        foreach (['en', 'ar'] as $locale) {
            $pages = [
                $this->actingAs($customer)->withSession(['locale' => $locale])->get(route('customer.complaints.index')),
                $this->actingAs($customer)->withSession(['locale' => $locale])->get(route('customer.complaints.show', $c)),
                $this->actingAs($worker)->withSession(['locale' => $locale])->get(route('worker.complaints.index')),
                $this->actingAs($worker)->withSession(['locale' => $locale])->get(route('worker.complaints.show', $c)),
            ];
            foreach ($pages as $res) {
                $res->assertOk();
                $res->assertDontSee('complaints.');
                $res->assertDontSee('nav.complaints');
            }
        }
    }

    public function test_customer_index_renders_in_arabic(): void
    {
        $customer = User::factory()->customer()->create();
        $this->complaintFor($customer);

        $this->actingAs($customer)->withSession(['locale' => 'ar'])
            ->get(route('customer.complaints.index'))
            ->assertOk()
            ->assertSee(__('complaints.filed_title', [], 'ar'), false);
    }

    /* --------------------------- regression --------------------------- */

    public function test_admin_complaint_pages_still_work(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->customer()->create();
        $c = $this->complaintFor($customer);

        $this->actingAs($admin)->get(route('admin.complaints.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.complaints.show', $c))->assertOk();
    }

    public function test_complaint_submission_is_unchanged(): void
    {
        $customer = User::factory()->customer()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('in_progress')->create();

        $this->actingAs($customer)
            ->post(route('customer.complaints.store', $request), [
                'category' => 'billing',
                'description' => 'Charged twice for one visit.',
            ])
            ->assertRedirect(route('customer.requests.show', $request))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('complaints', [
            'service_request_id' => $request->id,
            'reporter_id' => $customer->id,
            'status' => 'open',
        ]);
    }
}
