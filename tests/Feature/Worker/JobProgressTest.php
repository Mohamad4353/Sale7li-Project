<?php

namespace Tests\Feature\Worker;

use App\Models\Address;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\WorkerProfile;
use App\Models\WorkerService;
use App\Services\RequestWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 35.3 — the worker job progress experience. Same shared timeline as the
 * customer view, plus a plain-language "what to do next" hint and read-only
 * payment context. Authorization and workflow transitions are unchanged.
 */
class JobProgressTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: ServiceRequest} */
    private function job(string $status = 'accepted'): array
    {
        $worker = User::factory()->worker()->create();
        WorkerProfile::factory()->for($worker, 'user')->verified()->create();
        $request = ServiceRequest::factory()
            ->for(User::factory()->customer()->create(), 'customer')
            ->status($status)
            ->create([
                'service_category_id' => ServiceCategory::factory()->create()->id,
                'worker_id' => $worker->id,
            ]);

        return [$worker, $request];
    }

    public function test_the_assigned_worker_sees_the_timeline_and_a_next_step_hint(): void
    {
        [$worker, $request] = $this->job('accepted');

        $this->actingAs($worker)->get(route('worker.jobs.show', $request))
            ->assertOk()
            ->assertSee(__('workers.progress'))
            ->assertSee(__('workers.progress_hint.accepted'))
            ->assertSee(__('requests.steps.completed')); // future step still listed
    }

    public function test_the_hint_tracks_the_current_status(): void
    {
        [$worker, $request] = $this->job('on_the_way');

        $this->actingAs($worker)->get(route('worker.jobs.show', $request))
            ->assertOk()
            ->assertSee(__('workers.progress_hint.on_the_way'))
            ->assertDontSee(__('workers.progress_hint.accepted'));
    }

    public function test_available_actions_match_the_state(): void
    {
        [$worker, $pending] = $this->job('pending');
        $this->actingAs($worker)->get(route('worker.jobs.show', $pending))
            ->assertOk()
            ->assertSee(__('workers.accept_job'))
            ->assertSee(__('workers.decline'))
            ->assertDontSee(__('workers.save_status'));

        [$worker2, $accepted] = $this->job('accepted');
        $this->actingAs($worker2)->get(route('worker.jobs.show', $accepted))
            ->assertOk()
            ->assertSee(__('workers.save_status'))
            ->assertDontSee(__('workers.accept_job'));

        [$worker3, $done] = $this->job('completed');
        $this->actingAs($worker3)->get(route('worker.jobs.show', $done))
            ->assertOk()
            ->assertDontSee(__('workers.save_status'))
            ->assertSee(__('workers.progress_hint.completed'));
    }

    public function test_payment_context_reflects_the_request_payment_status(): void
    {
        [$worker, $request] = $this->job('in_progress');
        $request->update(['quoted_total' => 120, 'payment_status' => 'pending']);

        $this->actingAs($worker)->get(route('worker.jobs.show', $request))
            ->assertOk()
            ->assertSee(__('workers.payment_context'))
            ->assertSee(__('status.payment.pending'));

        $request->update(['payment_status' => 'paid']);
        $this->actingAs($worker)->get(route('worker.jobs.show', $request))
            ->assertOk()
            ->assertSee(__('status.payment.paid'));
    }

    public function test_the_timeline_shows_real_transition_times_from_history(): void
    {
        $workflow = app(RequestWorkflow::class);
        $customer = User::factory()->customer()->create();
        $category = ServiceCategory::factory()->create();
        $worker = User::factory()->worker()->create();
        WorkerProfile::factory()->for($worker, 'user')->verified()->create();
        WorkerService::create(['worker_id' => $worker->id, 'service_category_id' => $category->id, 'price_from' => 40, 'is_active' => true]);
        $address = Address::factory()->create(['user_id' => $customer->id]);

        $request = $workflow->create([
            'customer_id' => $customer->id, 'service_category_id' => $category->id, 'address_id' => $address->id,
            'problem_description' => 'x', 'preferred_date' => now()->addDay()->toDateString(),
            'preferred_time' => '10:00', 'status' => 'pending', 'payment_status' => 'pending',
        ]);
        $workflow->assign($request, $worker);
        $request = $workflow->workerAccept($request->fresh(), $worker);

        $this->actingAs($worker)->get(route('worker.jobs.show', $request))
            ->assertOk()
            ->assertSee('<time datetime=', false);
    }

    public function test_an_unassigned_worker_is_forbidden(): void
    {
        [, $request] = $this->job('accepted');

        $this->actingAs(User::factory()->worker()->create())
            ->get(route('worker.jobs.show', $request))->assertForbidden();
    }

    public function test_a_customer_cannot_reach_the_worker_job_page(): void
    {
        [, $request] = $this->job('accepted');

        $this->actingAs($request->customer)
            ->get(route('worker.jobs.show', $request))->assertForbidden();
    }

    public function test_a_guest_is_sent_to_login(): void
    {
        [, $request] = $this->job('accepted');

        $this->get(route('worker.jobs.show', $request))->assertRedirect(route('login'));
    }

    public function test_the_worker_job_progress_localises_to_arabic(): void
    {
        [$worker, $request] = $this->job('scheduled');

        $this->actingAs($worker)
            ->withSession(['locale' => 'ar'])
            ->get(route('worker.jobs.show', $request))
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee(__('workers.progress_hint.scheduled', [], 'ar'))
            ->assertSee(__('requests.steps.submitted', [], 'ar'))
            ->assertDontSee('workers.progress_hint.');
    }
}
