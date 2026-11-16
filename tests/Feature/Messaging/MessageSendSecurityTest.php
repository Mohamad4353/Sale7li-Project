<?php

namespace Tests\Feature\Messaging;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Fills the last thin spots in the messaging regression net: send-form
 * validation (StoreMessageRequest), sender attribution, a guest hitting the
 * private attachment route, and the Arabic render of the inbox + thread.
 * No production code changes — the messaging architecture is unchanged.
 */
class MessageSendSecurityTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{customer: User, worker: User, request: ServiceRequest} */
    private function thread(): array
    {
        $customer = User::factory()->customer()->create();
        $worker = User::factory()->worker()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('accepted')->create([
            'worker_id' => $worker->id,
        ]);

        return compact('customer', 'worker', 'request');
    }

    private function realImage(string $name = 'photo.png'): UploadedFile
    {
        return new UploadedFile(base_path('tests/Fixtures/pixel.png'), $name, 'image/png', null, true);
    }

    public function test_a_message_requires_a_body(): void
    {
        ['customer' => $customer, 'request' => $request] = $this->thread();

        $this->actingAs($customer)->from(route('customer.messages.show', $request))
            ->post(route('customer.messages.store', $request), ['attachments' => [$this->realImage()]])
            ->assertSessionHasErrors('body');

        $this->assertSame(0, Message::count());
    }

    public function test_message_attachments_must_be_raster_images_within_limits(): void
    {
        Storage::fake('local');
        ['customer' => $customer, 'request' => $request] = $this->thread();
        $from = fn () => $this->actingAs($customer)->from(route('customer.messages.show', $request));

        $from()->post(route('customer.messages.store', $request), [
            'body' => 'x', 'attachments' => [UploadedFile::fake()->create('logo.svg', 8, 'image/svg+xml')],
        ])->assertSessionHasErrors('attachments.0');

        $from()->post(route('customer.messages.store', $request), [
            'body' => 'x', 'attachments' => [UploadedFile::fake()->create('shell.php', 8, 'application/x-httpd-php')],
        ])->assertSessionHasErrors('attachments.0');

        $from()->post(route('customer.messages.store', $request), [
            'body' => 'x', 'attachments' => [UploadedFile::fake()->create('huge.jpg', 6000, 'image/jpeg')],
        ])->assertSessionHasErrors('attachments.0');

        $from()->post(route('customer.messages.store', $request), [
            'body' => 'x',
            'attachments' => array_fill(0, 6, $this->realImage()), // cap is 5
        ])->assertSessionHasErrors('attachments');

        $this->assertSame(0, Message::count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_the_sender_is_the_authenticated_user_not_a_client_value(): void
    {
        ['customer' => $customer, 'worker' => $worker, 'request' => $request] = $this->thread();

        $this->actingAs($customer)->post(route('customer.messages.store', $request), [
            'body' => 'Hello from the customer.',
            'sender_id' => $worker->id, // must be ignored
        ])->assertRedirect();

        $this->assertSame($customer->id, Message::sole()->sender_id);
    }

    public function test_a_guest_is_sent_to_login_from_the_private_attachment_route(): void
    {
        ['customer' => $customer, 'worker' => $worker, 'request' => $request] = $this->thread();
        $conversation = Conversation::create([
            'service_request_id' => $request->id, 'customer_id' => $customer->id, 'worker_id' => $worker->id,
        ]);
        $message = Message::create([
            'conversation_id' => $conversation->id, 'sender_id' => $customer->id,
            'body' => 'photo', 'attachments' => ['messages/x.jpg'],
        ]);

        $this->get(route('attachments.message', [$message, 0]))->assertRedirect(route('login'));
        $this->get(route('messages.index'))->assertRedirect(route('login'));
    }

    public function test_the_inbox_and_thread_render_in_arabic_without_raw_keys(): void
    {
        ['customer' => $customer, 'worker' => $worker, 'request' => $request] = $this->thread();
        Conversation::create([
            'service_request_id' => $request->id, 'customer_id' => $customer->id, 'worker_id' => $worker->id,
        ]);

        $this->actingAs($customer)->withSession(['locale' => 'ar'])->get(route('messages.index'))
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertDontSee('messages.inbox')
            ->assertDontSee('messages.empty');

        $this->actingAs($customer)->withSession(['locale' => 'ar'])
            ->get(route('customer.messages.show', $request))
            ->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertDontSee('messages.send')
            ->assertDontSee('messages.placeholder');
    }
}
