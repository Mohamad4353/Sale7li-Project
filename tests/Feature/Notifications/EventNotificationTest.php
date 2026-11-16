<?php

namespace Tests\Feature\Notifications;

use App\Models\Complaint;
use App\Models\Notification;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\WorkerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature 7 — event coverage additions on the EXISTING notification system:
 * "quotation received" for the customer, and "complaint update" for the people
 * involved. No second notification architecture.
 */
class EventNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_is_notified_when_a_professional_submits_a_quotation(): void
    {
        $customer = User::factory()->customer()->create();
        $worker = User::factory()->worker()->create();
        WorkerProfile::factory()->for($worker, 'user')->create(['verification_status' => 'verified']);
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('accepted')->create([
            'worker_id' => $worker->id,
        ]);

        $this->actingAs($worker)
            ->post(route('worker.jobs.quotation', $request), ['base_price' => 150])
            ->assertRedirect();

        $note = Notification::where('user_id', $customer->id)->where('type', 'quotation_received')->first();
        $this->assertNotNull($note);
        $this->assertSame($request->id, $note->service_request_id);
        $this->assertStringContainsString($request->reference, $note->renderedBody());
        $this->assertStringContainsString('$150.00', $note->renderedBody());
    }

    public function test_complaint_reporter_is_notified_only_when_the_status_moves(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->customer()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('completed')->create();
        $complaint = Complaint::factory()->create([
            'service_request_id' => $request->id,
            'reporter_id' => $customer->id,
            'status' => 'open',
        ]);

        // Same status -> no notification.
        $this->actingAs($admin)->patch(route('admin.complaints.update', $complaint), ['status' => 'open'])->assertRedirect();
        $this->assertDatabaseMissing('notifications', ['user_id' => $customer->id, 'type' => 'complaint_update']);

        // Status change -> reporter notified.
        $this->actingAs($admin)->patch(route('admin.complaints.update', $complaint), ['status' => 'investigating'])->assertRedirect();

        $note = Notification::where('user_id', $customer->id)->where('type', 'complaint_update')->firstOrFail();
        $this->assertSame($request->id, $note->service_request_id);
        $this->assertStringContainsString($request->reference, $note->renderedBody());
    }

    public function test_complaint_update_body_localizes_the_complaint_status(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->customer()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('completed')->create();
        $complaint = Complaint::factory()->create([
            'service_request_id' => $request->id, 'reporter_id' => $customer->id, 'status' => 'open',
        ]);

        $this->actingAs($admin)->patch(route('admin.complaints.update', $complaint), ['status' => 'resolved'])->assertRedirect();
        $note = Notification::where('user_id', $customer->id)->where('type', 'complaint_update')->firstOrFail();

        app()->setLocale('en');
        $en = $note->renderedBody();
        app()->setLocale('ar');
        $ar = $note->renderedBody();
        app()->setLocale('en');

        $this->assertStringContainsString(__('status.complaint.resolved', [], 'en'), $en);
        $this->assertStringContainsString(__('status.complaint.resolved', [], 'ar'), $ar);
        $this->assertStringNotContainsString('notifications.', $en);
        $this->assertStringNotContainsString('complaint_status_label', $ar);
    }

    public function test_assigned_worker_gets_a_worker_specific_complaint_update_not_the_reporter_wording(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->customer()->create();
        $worker = User::factory()->worker()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('completed')->create([
            'worker_id' => $worker->id,
        ]);
        $complaint = Complaint::factory()->create([
            'service_request_id' => $request->id,
            'reporter_id' => $customer->id,
            'status' => 'open',
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.complaints.update', $complaint), ['status' => 'investigating'])
            ->assertRedirect();

        // Reporter copy is unchanged: same type, same reporter-centric wording.
        $reporterNote = Notification::where('user_id', $customer->id)->where('type', 'complaint_update')->firstOrFail();
        $this->assertSame($request->id, $reporterNote->service_request_id);
        app()->setLocale('en');
        $this->assertStringContainsString('Your complaint', $reporterNote->renderedBody());
        app()->setLocale('ar');
        $this->assertStringContainsString('شكواك', $reporterNote->renderedBody());
        app()->setLocale('en');

        // Worker copy uses the new type; the old type is never written for them.
        $this->assertDatabaseMissing('notifications', ['user_id' => $worker->id, 'type' => 'complaint_update']);
        $workerNote = Notification::where('user_id', $worker->id)->where('type', 'complaint_update_worker')->firstOrFail();
        $this->assertSame($request->id, $workerNote->service_request_id);
        $this->assertSame($request->reference, $workerNote->data['reference']);

        app()->setLocale('en');
        $en = $workerNote->renderedBody();
        app()->setLocale('ar');
        $ar = $workerNote->renderedBody();
        app()->setLocale('en');

        // Placeholders still resolve, in both locales.
        $this->assertStringContainsString($request->reference, $en);
        $this->assertStringContainsString($request->reference, $ar);
        $this->assertStringContainsString(__('status.complaint.investigating', [], 'en'), $en);
        $this->assertStringContainsString(__('status.complaint.investigating', [], 'ar'), $ar);
        $this->assertStringNotContainsString('notifications.', $en);
        $this->assertStringNotContainsString('notifications.', $ar);
        $this->assertStringNotContainsString('complaint_status_label', $en.$ar);

        // Worker wording must not claim they filed the complaint.
        $this->assertStringNotContainsString('Your complaint', $en);
        $this->assertStringNotContainsString('شكواك', $ar);

        // Both titles resolve to real strings (no raw key leakage).
        app()->setLocale('en');
        $this->assertStringNotContainsString('notifications.', $workerNote->renderedTitle());
        app()->setLocale('ar');
        $this->assertStringNotContainsString('notifications.', $workerNote->renderedTitle());
        app()->setLocale('en');
    }

    public function test_whish_notifications_render_without_leaking_keys_in_both_locales(): void
    {
        $customer = User::factory()->customer()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('accepted')->create();

        foreach (['payment_submitted', 'whish_payment_approved', 'whish_payment_rejected', 'payment_confirmed'] as $type) {
            $note = Notification::record($customer->id, $type, $request->id, [
                'reference' => $request->reference,
                'amount' => '$99.00',
            ]);

            foreach (['en', 'ar'] as $locale) {
                app()->setLocale($locale);
                $body = $note->renderedBody();
                $title = $note->renderedTitle();
                $this->assertStringNotContainsString('notifications.', $body);
                $this->assertStringNotContainsString('notifications.', $title);
                $this->assertNotSame('', trim($body));
            }
            app()->setLocale('en');
        }
    }
}
