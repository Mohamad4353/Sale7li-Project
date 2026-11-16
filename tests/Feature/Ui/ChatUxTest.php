<?php

namespace Tests\Feature\Ui;

use App\Models\Conversation;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * P14-13 / P14-19 — mobile chat UX: taller mobile thread, a proper attachment
 * control, and a polite live region. The polling architecture, attachment
 * validation and controller logic are unchanged.
 */
class ChatUxTest extends TestCase
{
    use RefreshDatabase;

    private function thread(): array
    {
        $customer = User::factory()->customer()->create();
        $worker = User::factory()->worker()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('accepted')->create([
            'worker_id' => $worker->id,
        ]);
        Conversation::create([
            'service_request_id' => $request->id,
            'customer_id' => $customer->id,
            'worker_id' => $worker->id,
        ]);

        return [$customer, $request];
    }

    public function test_thread_is_a_polite_live_region(): void
    {
        [$customer, $request] = $this->thread();

        $html = $this->actingAs($customer)->get(route('messages.show', $request))->assertOk()->getContent();

        $this->assertStringContainsString('role="log"', $html);
        $this->assertStringContainsString('aria-live="polite"', $html);
        $this->assertStringContainsString('aria-relevant="additions text"', $html);
    }

    public function test_thread_uses_a_taller_mobile_height_but_keeps_the_desktop_height(): void
    {
        [$customer, $request] = $this->thread();

        $this->actingAs($customer)->get(route('messages.show', $request))->assertOk()
            ->assertSee('max-h-[65vh]', false)
            ->assertSee('sm:max-h-[55vh]', false);
    }

    public function test_attachment_control_is_intentional_yet_keeps_the_native_input_contract(): void
    {
        [$customer, $request] = $this->thread();

        // Phase 17: the attachment field is now a React island. The <x-react>
        // fallback slot still ships a real file input, so multipart submission
        // works before React mounts / with JS off, and server validation
        // (StoreMessageRequest) is unchanged.
        $this->actingAs($customer)->get(route('messages.show', $request))->assertOk()
            ->assertSee(__('messages.chat.attachments'))
            ->assertSee('name="attachments[]"', false)
            ->assertSee('accept="image/jpeg,image/png,image/webp"', false)
            ->assertSee('multiple', false)
            ->assertSee('data-react-root="AttachmentInput"', false);
    }

    public function test_polling_hook_and_send_form_are_still_present(): void
    {
        [$customer, $request] = $this->thread();

        $this->actingAs($customer)->get(route('messages.show', $request))->assertOk()
            ->assertSee('chatPoll(', false)
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee(__('messages.chat.send'));
    }
}
