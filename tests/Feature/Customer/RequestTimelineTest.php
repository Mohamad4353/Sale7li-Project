<?php

namespace Tests\Feature\Customer;

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
 * Phase 35.1 — the visual request timeline on the customer request detail page.
 * It is a read-only projection of the ActivityLog history RequestWorkflow
 * already writes; it never fabricates timestamps or workflow states.
 */
class RequestTimelineTest extends TestCase
{
    use RefreshDatabase;

    private function workerFor(ServiceCategory $category): User
    {
        $worker = User::factory()->worker()->create();
        WorkerProfile::factory()->for($worker, 'user')->verified()->create();
        WorkerService::create([
            'worker_id' => $worker->id,
            'service_category_id' => $category->id,
            'price_from' => 40,
            'is_active' => true,
        ]);

        return $worker;
    }

    /** Drives a real request from creation to $stopAt through the actual workflow. */
    private function driveTo(string $stopAt): array
    {
        $workflow = app(RequestWorkflow::class);
        $customer = User::factory()->customer()->create();
        $category = ServiceCategory::factory()->create();
        $worker = $this->workerFor($category);
        $address = Address::factory()->create(['user_id' => $customer->id]);

        $request = $workflow->create([
            'customer_id' => $customer->id,
            'service_category_id' => $category->id,
            'address_id' => $address->id,
            'problem_description' => 'Kitchen tap is leaking.',
            'preferred_date' => now()->addDay()->toDateString(),
            'preferred_time' => '10:00',
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);

        $steps = ['assigned', 'accepted', 'scheduled', 'on_the_way', 'in_progress', 'completed'];
        foreach ($steps as $step) {
            if ($step === 'assigned') {
                $workflow->assign($request, $worker);
            } elseif ($step === 'accepted') {
                $request = $workflow->workerAccept($request->fresh(), $worker);
            } else {
                $request = $workflow->transition($request->fresh(), $step);
            }
            if ($step === $stopAt) {
                break;
            }
        }

        return [$customer, $request->fresh()];
    }

    public function test_the_timeline_renders_every_lifecycle_step(): void
    {
        [$customer, $request] = $this->driveTo('scheduled');

        $this->actingAs($customer)->get(route('customer.requests.show', $request))
            ->assertOk()
            ->assertSee(__('requests.steps.submitted'))
            ->assertSee(__('requests.steps.assigned'))
            ->assertSee(__('requests.steps.accepted'))
            ->assertSee(__('requests.steps.scheduled'))
            ->assertSee(__('requests.steps.on_the_way'))
            ->assertSee(__('requests.steps.in_progress'))
            ->assertSee(__('requests.steps.completed'));
    }

    public function test_completed_steps_are_marked_done_with_a_timestamp(): void
    {
        [$customer, $request] = $this->driveTo('on_the_way');
        $acceptedAt = $request->fresh(); // history recorded during driveTo

        $html = $this->actingAs($customer)->get(route('customer.requests.show', $request))
            ->assertOk()
            ->assertSee(__('requests.timeline_a11y.done'))
            ->getContent();

        // The "accepted" transition time is a real ActivityLog row → rendered as <time>.
        $this->assertStringContainsString('<time datetime=', $html);
    }

    public function test_the_current_step_is_emphasised(): void
    {
        [$customer, $request] = $this->driveTo('in_progress');

        $this->actingAs($customer)->get(route('customer.requests.show', $request))
            ->assertOk()
            ->assertSee(__('requests.timeline_a11y.current'))
            ->assertSee(__('status.request.in_progress'));
    }

    public function test_future_steps_are_shown_as_upcoming(): void
    {
        [$customer, $request] = $this->driveTo('accepted');

        $this->actingAs($customer)->get(route('customer.requests.show', $request))
            ->assertOk()
            ->assertSee(__('requests.timeline_a11y.upcoming'))
            ->assertSee(__('requests.steps.completed'));
    }

    public function test_a_terminal_state_shows_the_cancelled_row(): void
    {
        $customer = User::factory()->customer()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('cancelled')->create();

        $this->actingAs($customer)->get(route('customer.requests.show', $request))
            ->assertOk()
            ->assertSee(__('status.request.cancelled'))
            ->assertSee('border-rose-300', false);
    }

    public function test_a_rejected_request_also_shows_a_terminal_row(): void
    {
        $customer = User::factory()->customer()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('rejected')->create();

        $this->actingAs($customer)->get(route('customer.requests.show', $request))
            ->assertOk()
            ->assertSee(__('status.request.rejected'))
            ->assertSee('border-rose-300', false)      // same red terminal treatment as cancelled
            ->assertSee(__('requests.steps.submitted')); // earlier nodes still listed
    }

    public function test_a_fully_completed_request_shows_the_whole_timeline_with_real_times(): void
    {
        [$customer, $request] = $this->driveTo('completed');

        $html = $this->actingAs($customer)->get(route('customer.requests.show', $request))
            ->assertOk()
            ->assertSee(__('requests.steps.submitted'))
            ->assertSee(__('requests.steps.assigned'))
            ->assertSee(__('requests.steps.completed'))
            ->assertSee(__('requests.timeline_a11y.done'))
            ->getContent();

        // submitted + assigned + accepted + scheduled + on_the_way + in_progress
        // + completed each have a recorded time → seven <time> elements.
        $this->assertGreaterThanOrEqual(7, substr_count($html, '<time datetime='));
    }

    public function test_the_timeline_is_a_semantic_ordered_list_with_machine_times(): void
    {
        [$customer, $request] = $this->driveTo('scheduled');

        $html = $this->actingAs($customer)->get(route('customer.requests.show', $request))
            ->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<ol[^>]*>.*<li[^>]*>.*<\/li>.*<\/ol>/s', $html);
        $this->assertMatchesRegularExpression('/<time datetime="\d{4}-\d\d-\d\dT\d\d:\d\d:\d\d/', $html);
    }

    public function test_an_assigned_but_unconfirmed_request_names_the_professional_on_the_timeline(): void
    {
        [$customer, $request] = $this->driveTo('assigned'); // still 'pending', worker attached

        $this->actingAs($customer)->get(route('customer.requests.show', $request))
            ->assertOk()
            ->assertSee(__('requests.assigned_to', ['name' => $request->worker->name]));
    }

    public function test_the_timeline_survives_a_request_with_no_recorded_history(): void
    {
        $customer = User::factory()->customer()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('in_progress')->create();

        $this->actingAs($customer)->get(route('customer.requests.show', $request))
            ->assertOk()
            ->assertSee(__('requests.steps.in_progress'));
    }

    public function test_the_timeline_is_localised_to_arabic_and_rtl(): void
    {
        [$customer, $request] = $this->driveTo('scheduled');

        $response = $this->actingAs($customer)
            ->withSession(['locale' => 'ar'])
            ->get(route('customer.requests.show', $request));

        $response->assertOk();
        $response->assertSee('<html lang="ar" dir="rtl">', false);
        $response->assertSee(__('requests.steps.submitted', [], 'ar'));
        $response->assertSee(__('requests.steps.scheduled', [], 'ar'));
        $response->assertDontSee('requests.steps.');
    }

    public function test_another_customer_cannot_see_the_timeline(): void
    {
        [$owner, $request] = $this->driveTo('accepted');

        $this->actingAs(User::factory()->customer()->create())
            ->get(route('customer.requests.show', $request))->assertForbidden();
    }

    public function test_a_guest_is_sent_to_login(): void
    {
        $customer = User::factory()->customer()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('accepted')->create();

        $this->get(route('customer.requests.show', $request))->assertRedirect(route('login'));
    }
}
