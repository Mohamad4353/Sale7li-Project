<?php

namespace Tests\Feature\Notifications;

use App\Models\Notification;
use App\Models\Quotation;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Payments\BuildsPaidJobs;
use Tests\TestCase;

class QuotationNotificationTest extends TestCase
{
    use BuildsPaidJobs, RefreshDatabase;

    /**
     * @return array{customer: User, worker: User, request: ServiceRequest, quotation: Quotation}
     */
    private function pendingQuotation(): array
    {
        $customer = User::factory()->customer()->create();
        $worker = User::factory()->worker()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('accepted')->create([
            'service_category_id' => ServiceCategory::factory()->create()->id,
            'worker_id' => $worker->id,
        ]);

        $this->actingAs($worker)->post(route('worker.jobs.quotation', $request), ['base_price' => 150])->assertRedirect();
        $quotation = Quotation::where('service_request_id', $request->id)->firstOrFail();

        return compact('customer', 'worker', 'request', 'quotation');
    }

    public function test_worker_is_notified_when_a_quotation_is_accepted(): void
    {
        ['customer' => $customer, 'worker' => $worker, 'request' => $request, 'quotation' => $quotation] = $this->pendingQuotation();

        $this->actingAs($customer)->post(route('customer.quotations.accept', $quotation))->assertRedirect();

        $n = Notification::where('user_id', $worker->id)->where('type', 'quotation_accepted')->first();
        $this->assertNotNull($n);
        $this->assertSame($request->id, $n->service_request_id);
        $this->assertSame($request->reference, $n->data['reference']);
    }

    public function test_worker_is_notified_when_a_quotation_is_rejected(): void
    {
        ['customer' => $customer, 'worker' => $worker, 'request' => $request, 'quotation' => $quotation] = $this->pendingQuotation();

        $this->actingAs($customer)->post(route('customer.quotations.reject', $quotation))->assertRedirect();

        $n = Notification::where('user_id', $worker->id)->where('type', 'quotation_rejected')->first();
        $this->assertNotNull($n);
        $this->assertSame($request->id, $n->service_request_id);
    }

    public function test_customer_does_not_receive_the_worker_only_quotation_notification(): void
    {
        ['customer' => $customer, 'worker' => $worker, 'quotation' => $quotation] = $this->pendingQuotation();

        $this->actingAs($customer)->post(route('customer.quotations.accept', $quotation))->assertRedirect();

        $this->assertDatabaseMissing('notifications', [
            'user_id' => $customer->id,
            'type' => 'quotation_accepted',
        ]);
    }

    public function test_quotation_notification_deep_links_to_the_worker_job(): void
    {
        ['customer' => $customer, 'worker' => $worker, 'request' => $request, 'quotation' => $quotation] = $this->pendingQuotation();
        $this->actingAs($customer)->post(route('customer.quotations.accept', $quotation))->assertRedirect();

        $n = Notification::where('user_id', $worker->id)->where('type', 'quotation_accepted')->firstOrFail();

        $this->actingAs($worker)
            ->get(route('notifications.open', $n))
            ->assertRedirect(route('worker.jobs.show', $request));
    }

    public function test_quotation_notification_renders_in_both_locales(): void
    {
        ['customer' => $customer, 'worker' => $worker, 'quotation' => $quotation] = $this->pendingQuotation();
        $this->actingAs($customer)->post(route('customer.quotations.accept', $quotation))->assertRedirect();

        $this->actingAs($worker)->withSession(['locale' => 'en'])
            ->get(route('notifications.index'))->assertOk()->assertSee('was accepted');

        $this->actingAs($worker)->withSession(['locale' => 'ar'])
            ->get(route('notifications.index'))->assertOk()->assertSee('تم قبول عرض سعرك');
    }

    public function test_accepting_a_quotation_does_not_change_totals(): void
    {
        ['customer' => $customer, 'request' => $request, 'quotation' => $quotation] = $this->pendingQuotation();

        $this->actingAs($customer)->post(route('customer.quotations.accept', $quotation))->assertRedirect();

        $this->assertSame(
            (string) $quotation->fresh()->total,
            (string) $request->fresh()->quoted_total,
        );
    }
}
