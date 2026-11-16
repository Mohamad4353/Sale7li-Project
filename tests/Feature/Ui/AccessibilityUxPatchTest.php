<?php

namespace Tests\Feature\Ui;

use App\Models\Address;
use App\Models\Appointment;
use App\Models\Payment;
use App\Models\Quotation;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\WorkerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Attack Point #14 remediation — surgical accessibility / presentation fixes.
 * Covers B-1..B-5 and C-1/C-2. B-6 (field hint association) is exercised by
 * Tests\Feature\Components\FieldAccessibilityTest.
 */
class AccessibilityUxPatchTest extends TestCase
{
    use RefreshDatabase;

    /* ---------------------------------------------------------------- B-1 */

    public function test_b1_booking_category_radio_group_has_an_accessible_group_label(): void
    {
        ServiceCategory::factory()->count(3)->create(['is_active' => true]);
        $customer = User::factory()->customer()->create(['email_verified_at' => now()]);
        Address::factory()->for($customer, 'user')->create();

        $html = $this->actingAs($customer)->get(route('customer.booking.create'))->assertOk()->getContent();

        // The radios are wrapped in a fieldset whose legend names the group.
        $this->assertMatchesRegularExpression(
            '/<fieldset[^>]*>\s*<legend class="sr-only">'.preg_quote(__('bookings.step_service'), '/').'<\/legend>/s',
            $html,
        );
        // The radios themselves are unchanged (same name, one per active category).
        $this->assertSame(3, substr_count($html, 'name="service_category_id"'));
    }

    public function test_b1_booking_category_group_label_is_localized_in_arabic(): void
    {
        ServiceCategory::factory()->create(['is_active' => true]);
        $customer = User::factory()->customer()->create(['email_verified_at' => now()]);
        Address::factory()->for($customer, 'user')->create();

        $html = $this->actingAs($customer)->withSession(['locale' => 'ar'])
            ->get(route('customer.booking.create'))->assertOk()->getContent();

        $this->assertStringContainsString('<legend class="sr-only">'.__('bookings.step_service', [], 'ar').'</legend>', $html);
    }

    public function test_b1_registration_role_radio_group_has_a_fieldset_legend_and_no_orphaned_label(): void
    {
        $html = $this->get(route('register'))->assertOk()->getContent();

        // A real grouping element with a visible, localized legend.
        $this->assertStringContainsString('<legend class="field-label">', $html);
        $this->assertMatchesRegularExpression(
            '/<legend class="field-label">\s*'.preg_quote(__('auth.account_type'), '/').'/s',
            $html,
        );
        // The previously orphaned <label for="f-role"> is gone.
        $this->assertStringNotContainsString('for="f-role"', $html);
        // Registration still submits the same field.
        $this->assertStringContainsString('type="radio" name="role" value="customer"', $html);
        $this->assertStringContainsString('type="radio" name="role" value="worker"', $html);
    }

    public function test_b1_registration_still_creates_the_chosen_role(): void
    {
        $this->post('/register', [
            'name' => 'Group Legend Tester',
            'email' => 'legend@example.com',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
            'role' => 'worker',
        ])->assertRedirect(route('verification.notice'));

        $this->assertSame('worker', User::where('email', 'legend@example.com')->firstOrFail()->role);
    }

    /* ---------------------------------------------------------------- B-2 */

    public function test_b2_customer_request_attachment_links_have_accessible_names(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('requests/a.jpg', 'x');
        Storage::disk('local')->put('requests/b.jpg', 'x');

        $customer = User::factory()->customer()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('pending')->create([
            'problem_images' => ['requests/a.jpg', 'requests/b.jpg'],
        ]);

        $html = $this->actingAs($customer)->get(route('customer.requests.show', $request))->assertOk()->getContent();

        // Each clickable thumbnail carries a meaningful, localized name.
        $this->assertStringContainsString('alt="'.__('requests.attachment_number', ['number' => 1]).'"', $html);
        $this->assertStringContainsString('alt="'.__('requests.attachment_number', ['number' => 2]).'"', $html);
        // The attachment <img> is no longer the audited empty-alt link content.
        $this->assertStringNotContainsString('problem/0" alt=""', $html);
        $this->assertStringNotContainsString('problem/1" alt=""', $html);
    }

    public function test_b2_admin_request_problem_and_completion_thumbnails_have_accessible_names(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('requests/p.jpg', 'x');
        Storage::disk('local')->put('completions/c.jpg', 'x');

        $admin = User::factory()->admin()->create();
        $request = ServiceRequest::factory()->status('completed')->create([
            'problem_images' => ['requests/p.jpg'],
            'completion_images' => ['completions/c.jpg'],
        ]);

        $html = $this->actingAs($admin)->get(route('admin.requests.show', $request))->assertOk()->getContent();

        $this->assertStringContainsString('alt="'.__('requests.attachment_number', ['number' => 1]).'"', $html);
        $this->assertStringContainsString('alt="'.__('requests.completion_image_number', ['number' => 1]).'"', $html);
        $this->assertStringNotContainsString('problem/0" alt=""', $html);
        $this->assertStringNotContainsString('completion/0" alt=""', $html);
    }

    /* ---------------------------------------------------------------- B-3 */

    public function test_b3_booking_page_exposes_stable_focus_targets_for_the_ai_assistant(): void
    {
        // The focus moves themselves live in resources/js/app.js
        // (aiSuggestion.apply/dismiss). This asserts the DOM ids those handlers
        // target actually exist and are stable — the strongest deterministic
        // regression available without a headless-browser Alpine harness.
        ServiceCategory::factory()->create(['is_active' => true]);
        $customer = User::factory()->customer()->create(['email_verified_at' => now()]);
        Address::factory()->for($customer, 'user')->create();

        $html = $this->actingAs($customer)->get(route('customer.booking.create'))->assertOk()->getContent();

        $this->assertStringContainsString('id="ai-assistant-analyze"', $html);   // dismiss() focus target
        $this->assertStringContainsString('id="f-problem_description"', $html);   // apply() focus target
    }

    /* ---------------------------------------------------------------- B-4 */

    public function test_b4_bar_chart_is_not_collapsed_behind_role_img_and_keeps_label_value_text(): void
    {
        $series = [['label' => 'Pending', 'value' => 3], ['label' => 'Completed', 'value' => 7]];

        $html = Blade::render('<x-chart-bars :series="$series" title="Jobs by status" />', compact('series'));

        $this->assertStringNotContainsString('role="img"', $html);
        // Every data point is still real, readable text.
        $this->assertStringContainsString('Pending', $html);
        $this->assertStringContainsString('Completed', $html);
        $this->assertStringContainsString('>3<', $html);
        $this->assertStringContainsString('>7<', $html);
    }

    public function test_b4_line_chart_exposes_the_plotted_points_to_assistive_tech(): void
    {
        $series = [['label' => 'Mon', 'value' => 2], ['label' => 'Tue', 'value' => 5]];

        $html = Blade::render('<x-chart-line :series="$series" title="Requests" />', compact('series'));

        // The SVG keeps its visual identity...
        $this->assertStringContainsString('role="img"', $html);
        // ...and a screen-reader-only list carries the actual values.
        $this->assertStringContainsString('<ul class="sr-only">', $html);
        $this->assertStringContainsString('<li>Mon: 2</li>', $html);
        $this->assertStringContainsString('<li>Tue: 5</li>', $html);
    }

    /* ---------------------------------------------------------------- B-5 */

    public function test_b5_report_and_reschedule_disclosures_expose_state_and_control(): void
    {
        $worker = User::factory()->worker()->create();
        $customer = User::factory()->customer()->create();
        $category = ServiceCategory::factory()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('scheduled')->create([
            'service_category_id' => $category->id,
            'worker_id' => $worker->id,
        ]);
        Appointment::create([
            'service_request_id' => $request->id,
            'worker_id' => $worker->id,
            'scheduled_date' => now()->addDays(3)->toDateString(),
            'scheduled_time' => '14:00',
            'status' => 'scheduled',
        ]);

        $html = $this->actingAs($customer)->get(route('customer.requests.show', $request))->assertOk()->getContent();

        // Report a problem
        $this->assertStringContainsString('aria-controls="report-problem-form"', $html);
        $this->assertStringContainsString('x-bind:aria-expanded="open"', $html);
        $this->assertStringContainsString('id="report-problem-form"', $html);

        // Reschedule (only rendered when the request is scheduled)
        $this->assertStringContainsString('aria-controls="reschedule-form"', $html);
        $this->assertStringContainsString('id="reschedule-form"', $html);
    }

    /* ---------------------------------------------------------------- C-1 */

    public function test_c1_customer_appointment_card_shows_hh_mm_without_seconds(): void
    {
        $worker = User::factory()->worker()->create();
        $customer = User::factory()->customer()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('scheduled')->create([
            'service_category_id' => ServiceCategory::factory()->create()->id,
            'worker_id' => $worker->id,
        ]);
        Appointment::create([
            'service_request_id' => $request->id,
            'worker_id' => $worker->id,
            'scheduled_date' => now()->addDays(2)->toDateString(),
            'scheduled_time' => '09:30:00',
            'status' => 'scheduled',
        ]);

        $html = $this->actingAs($customer)->get(route('customer.requests.show', $request))->assertOk()->getContent();

        $this->assertStringContainsString('09:30', $html);
        $this->assertStringNotContainsString('09:30:00', $html);
    }

    public function test_c1_worker_appointment_card_shows_hh_mm_without_seconds(): void
    {
        $worker = User::factory()->worker()->create();
        WorkerProfile::factory()->for($worker, 'user')->verified()->create();
        $customer = User::factory()->customer()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('scheduled')->create([
            'service_category_id' => ServiceCategory::factory()->create()->id,
            'worker_id' => $worker->id,
        ]);
        Appointment::create([
            'service_request_id' => $request->id,
            'worker_id' => $worker->id,
            'scheduled_date' => now()->addDays(2)->toDateString(),
            'scheduled_time' => '16:45:00',
            'status' => 'scheduled',
        ]);

        $html = $this->actingAs($worker)->get(route('worker.jobs.show', $request))->assertOk()->getContent();

        // Scope to the read-only "Appointment" card — the separate job-status
        // scheduling form keeps a raw <input type="time"> value and is out of
        // scope for C-1 (presentation-only) per the fix brief.
        $pos = strpos($html, '>'.__('workers.appointment').'</h3>');
        $this->assertNotFalse($pos);
        $card = substr($html, $pos, 220);

        $this->assertStringContainsString('16:45', $card);
        $this->assertStringNotContainsString('16:45:00', $card);
    }

    /* ---------------------------------------------------------------- C-2 */

    public function test_c2_customer_amount_due_uses_the_money_component_with_cents(): void
    {
        $customer = User::factory()->customer()->create();
        $worker = User::factory()->worker()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('accepted')->create([
            'service_category_id' => ServiceCategory::factory()->create()->id,
            'worker_id' => $worker->id,
        ]);
        $this->actingAs($worker)->post(route('worker.jobs.quotation', $request), ['base_price' => 250])->assertRedirect();
        $this->actingAs($customer)
            ->post(route('customer.quotations.accept', Quotation::where('service_request_id', $request->id)->firstOrFail()))
            ->assertRedirect();

        $html = $this->actingAs($customer)->get(route('customer.requests.show', $request))->assertOk()->getContent();

        // The amount-due row renders through <x-money> (nested span) with the
        // established two-decimal convention.
        $pos = strpos($html, '>'.__('payments.amount_due').'</span>');
        $this->assertNotFalse($pos);
        $fragment = substr($html, $pos, 200);

        $this->assertStringContainsString('$250.00', $fragment);
        $this->assertGreaterThanOrEqual(2, substr_count($fragment, '<span'), 'amount-due should wrap <x-money>');
    }

    public function test_c2_worker_dashboard_earnings_uses_the_project_currency_convention_with_cents(): void
    {
        $worker = User::factory()->worker()->create();
        WorkerProfile::factory()->for($worker, 'user')->create();
        Payment::factory()->paid()->create([
            'worker_id' => $worker->id,
            'amount' => 100, 'commission_amount' => 10, 'worker_net' => 90,
        ]);

        $html = $this->actingAs($worker)->get(route('worker.dashboard'))->assertOk()->getContent();

        // Same presentation as the earnings page headline (cents included).
        $this->assertStringContainsString('$90.00', $html);
    }
}
