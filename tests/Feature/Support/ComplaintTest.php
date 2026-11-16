<?php

namespace Tests\Feature\Support;

use App\Models\Complaint;
use App\Models\Payment;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Js;
use Tests\Feature\Payments\BuildsPaidJobs;
use Tests\TestCase;

class ComplaintTest extends TestCase
{
    use BuildsPaidJobs, RefreshDatabase;

    private function ownRequest(User $customer): ServiceRequest
    {
        return ServiceRequest::factory()->for($customer, 'customer')->status('in_progress')->create();
    }

    public function test_customer_can_report_a_problem_for_their_own_request(): void
    {
        $customer = User::factory()->customer()->create();
        $request = $this->ownRequest($customer);

        $this->actingAs($customer)
            ->from(route('customer.requests.show', $request))
            ->post(route('customer.complaints.store', $request), [
                'category' => 'work_quality',
                'description' => 'The technician left the job unfinished.',
            ])
            ->assertRedirect(route('customer.requests.show', $request))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('complaints', [
            'service_request_id' => $request->id,
            'reporter_id' => $customer->id,
            'category' => 'work_quality',
            'status' => 'open',
        ]);
    }

    public function test_customer_cannot_report_a_problem_for_another_customers_request(): void
    {
        $owner = User::factory()->customer()->create();
        $intruder = User::factory()->customer()->create();
        $request = $this->ownRequest($owner);

        $this->actingAs($intruder)
            ->post(route('customer.complaints.store', $request), [
                'category' => 'work_quality',
                'description' => 'Not my request but I am trying.',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('complaints', 0);
    }

    public function test_guest_cannot_create_a_complaint(): void
    {
        $request = ServiceRequest::factory()->status('pending')->create();

        $this->post(route('customer.complaints.store', $request), [
            'category' => 'work_quality',
            'description' => 'x',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseCount('complaints', 0);
    }

    public function test_worker_cannot_use_the_customer_complaint_route(): void
    {
        $request = ServiceRequest::factory()->status('pending')->create();

        $this->actingAs(User::factory()->worker()->create())
            ->post(route('customer.complaints.store', $request), [
                'category' => 'work_quality',
                'description' => 'x',
            ])
            ->assertForbidden();
    }

    public function test_complaint_stores_only_server_controlled_status_priority_and_reporter(): void
    {
        $customer = User::factory()->customer()->create();
        $other = User::factory()->customer()->create();
        $request = $this->ownRequest($customer);

        $this->actingAs($customer)->post(route('customer.complaints.store', $request), [
            'category' => 'billing',
            'description' => 'Charged twice.',
            'status' => 'resolved',
            'priority' => 'urgent',
            'reporter_id' => $other->id,
            'internal_notes' => 'hacked note',
        ])->assertRedirect();

        $complaint = Complaint::firstOrFail();
        $this->assertSame('open', $complaint->status);
        $this->assertSame($customer->id, $complaint->reporter_id);
        $this->assertNotSame('urgent', $complaint->priority);
        $this->assertNull($complaint->internal_notes);
    }

    public function test_complaint_category_must_be_in_the_allow_list(): void
    {
        $customer = User::factory()->customer()->create();
        $request = $this->ownRequest($customer);

        $this->actingAs($customer)
            ->from(route('customer.requests.show', $request))
            ->post(route('customer.complaints.store', $request), [
                'category' => 'totally-made-up',
                'description' => 'x',
            ])
            ->assertSessionHasErrors('category');

        $this->assertDatabaseCount('complaints', 0);
    }

    public function test_request_detail_page_exposes_the_report_a_problem_action(): void
    {
        $customer = User::factory()->customer()->create();
        $request = $this->ownRequest($customer);

        $this->actingAs($customer)
            ->get(route('customer.requests.show', $request))
            ->assertOk()
            ->assertSee(route('customer.complaints.store', $request), false);
    }

    /**
     * Regression: the "Report a problem" toggle's x-text previously called the
     * js Blade directive directly inside x-button's plain attribute, a
     * position Blade does not compile — the literal, uncompiled directive text
     * was rendered on the page and the toggle's dynamic label never worked.
     * Fixed by pre-computing the values and echoing them instead (see
     * resources/views/customer/requests/show.blade.php).
     */
    public function test_the_report_a_problem_toggle_has_no_uncompiled_js_expression(): void
    {
        $customer = User::factory()->customer()->create();
        $request = $this->ownRequest($customer);

        $html = $this->actingAs($customer)
            ->get(route('customer.requests.show', $request))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('@js(', $html);
        // The toggle button itself, and the two labels it switches between,
        // still render — both strings appear as the x-text alternatives,
        // rendered via Illuminate\Support\Js::from() (single-quoted JS string
        // literals), the same safe form @js() itself would have produced.
        $this->assertStringContainsString(__('complaints.report_title'), $html);
        $this->assertStringContainsString((string) Js::from(__('messages.close')), $html);
        $this->assertStringContainsString((string) Js::from(__('complaints.report_title')), $html);
    }

    public function test_support_page_is_reachable_and_shows_a_configurable_address(): void
    {
        $this->get(route('support'))->assertRedirect(route('login'));

        config(['sale7li.support_email' => 'help@sale7li.test']);

        $this->actingAs(User::factory()->customer()->create())
            ->get(route('support'))
            ->assertOk()
            ->assertSee('help@sale7li.test');
    }

    public function test_admin_can_view_the_complaints_queue_and_a_complaint_detail(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->customer()->create();
        $request = $this->ownRequest($customer);
        $complaint = Complaint::create([
            'service_request_id' => $request->id,
            'reporter_id' => $customer->id,
            'category' => 'no_show',
            'description' => 'Nobody came.',
            'status' => 'open',
            'priority' => 'medium',
        ]);

        $this->actingAs($admin)->get(route('admin.complaints.index'))
            ->assertOk()
            ->assertSee($request->reference)
            ->assertSee($customer->name);

        $this->actingAs($admin)->get(route('admin.complaints.show', $complaint))
            ->assertOk()
            ->assertSee($request->reference)
            ->assertSee('Nobody came.');
    }

    public function test_non_admin_cannot_view_the_complaints_queue(): void
    {
        $this->get(route('admin.complaints.index'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->customer()->create())->get(route('admin.complaints.index'))->assertForbidden();
        $this->actingAs(User::factory()->worker()->create())->get(route('admin.complaints.index'))->assertForbidden();
    }

    public function test_admin_can_update_complaint_status_and_internal_notes(): void
    {
        $admin = User::factory()->admin()->create();
        $complaint = Complaint::create([
            'reporter_id' => User::factory()->customer()->create()->id,
            'category' => 'other',
            'description' => 'x',
            'status' => 'open',
            'priority' => 'medium',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.complaints.show', $complaint))
            ->patch(route('admin.complaints.update', $complaint), [
                'status' => 'investigating',
                'internal_notes' => 'Reached out to the professional.',
            ])
            ->assertRedirect(route('admin.complaints.show', $complaint))
            ->assertSessionHas('success');

        $complaint->refresh();
        $this->assertSame('investigating', $complaint->status);
        $this->assertSame('Reached out to the professional.', $complaint->internal_notes);
    }

    public function test_admin_cannot_set_an_invalid_complaint_status(): void
    {
        $admin = User::factory()->admin()->create();
        $complaint = Complaint::create([
            'reporter_id' => User::factory()->customer()->create()->id,
            'category' => 'other',
            'description' => 'x',
            'status' => 'open',
            'priority' => 'medium',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.complaints.show', $complaint))
            ->patch(route('admin.complaints.update', $complaint), ['status' => 'deleted'])
            ->assertSessionHasErrors('status');

        $this->assertSame('open', $complaint->fresh()->status);
    }

    public function test_updating_a_complaint_never_mutates_payment_or_refund_data(): void
    {
        ['request' => $request] = $this->quotedCompletedJob(['base_price' => 200]);
        $this->actingAs($request->customer)->post(route('customer.payments.pay', $request))->assertRedirect();

        $payment = Payment::where('service_request_id', $request->id)->firstOrFail();
        $before = $payment->only(['amount', 'commission_rate', 'commission_amount', 'worker_net', 'status']);

        $admin = User::factory()->admin()->create();
        $complaint = Complaint::create([
            'service_request_id' => $request->id,
            'reporter_id' => $request->customer_id,
            'category' => 'billing',
            'description' => 'dispute',
            'status' => 'open',
            'priority' => 'medium',
        ]);

        $this->actingAs($admin)->patch(route('admin.complaints.update', $complaint), [
            'status' => 'resolved',
            'internal_notes' => 'No refund warranted.',
        ])->assertRedirect();

        $payment->refresh();
        $this->assertSame($before, $payment->only(['amount', 'commission_rate', 'commission_amount', 'worker_net', 'status']));
        $this->assertDatabaseCount('refunds', 0);
    }
}
