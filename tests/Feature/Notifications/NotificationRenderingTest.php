<?php

namespace Tests\Feature\Notifications;

use App\Models\Address;
use App\Models\Notification;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\RequestWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationRenderingTest extends TestCase
{
    use RefreshDatabase;

    private function submittedRequestFor(User $customer): ServiceRequest
    {
        return app(RequestWorkflow::class)->create([
            'customer_id' => $customer->id,
            'service_category_id' => ServiceCategory::factory()->create()->id,
            'address_id' => Address::factory()->for($customer, 'user')->create()->id,
            'problem_description' => 'Leaking tap.',
            'preferred_date' => now()->addDay()->toDateString(),
            'preferred_time' => '10:00',
            'is_emergency' => false,
            'problem_images' => [],
        ]);
    }

    public function test_english_user_sees_an_english_notification_body_with_the_reference(): void
    {
        $customer = User::factory()->customer()->create(['locale' => 'en']);
        $request = $this->submittedRequestFor($customer);

        $this->actingAs($customer)->withSession(['locale' => 'en'])
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee($request->reference)
            ->assertSee('has been submitted');
    }

    public function test_arabic_user_sees_an_arabic_notification_body(): void
    {
        $customer = User::factory()->customer()->create(['locale' => 'ar']);
        $request = $this->submittedRequestFor($customer);

        $this->actingAs($customer)->withSession(['locale' => 'ar'])
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee($request->reference)
            ->assertSee('تم إرسال طلبك')
            ->assertDontSee('has been submitted');
    }

    public function test_status_change_notification_localizes_the_status_label(): void
    {
        $customer = User::factory()->customer()->create();
        $worker = User::factory()->worker()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('on_the_way')->create([
            'worker_id' => $worker->id,
        ]);
        app(RequestWorkflow::class)->transition($request, 'in_progress');

        $this->actingAs($customer)->withSession(['locale' => 'en'])
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('In progress');

        $this->actingAs($customer)->withSession(['locale' => 'ar'])
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('قيد التنفيذ');
    }

    public function test_legacy_notification_without_structured_data_renders_its_stored_message(): void
    {
        $customer = User::factory()->customer()->create();
        Notification::create([
            'user_id' => $customer->id,
            'type' => 'request_status',
            'title' => 'Legacy title',
            'message' => 'This legacy body was stored as prose.',
            'data' => null,
        ]);

        $this->actingAs($customer)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('This legacy body was stored as prose.');
    }

    public function test_unknown_notification_type_falls_back_to_stored_text_without_key_leakage(): void
    {
        $customer = User::factory()->customer()->create();
        Notification::create([
            'user_id' => $customer->id,
            'type' => 'brand_new_unmapped_type',
            'title' => 'Fallback title',
            'message' => 'Fallback body text.',
        ]);

        $this->actingAs($customer)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Fallback body text.')
            ->assertDontSee('notifications.messages.')
            ->assertDontSee('notifications.types.');
    }

    public function test_structured_notification_never_leaks_a_raw_translation_key(): void
    {
        $customer = User::factory()->customer()->create();
        $request = $this->submittedRequestFor($customer);

        $html = $this->actingAs($customer)->get(route('notifications.index'))->getContent();

        $this->assertStringNotContainsString('notifications.messages.', $html);
        $this->assertStringNotContainsString('notifications.types.', $html);
    }
}
