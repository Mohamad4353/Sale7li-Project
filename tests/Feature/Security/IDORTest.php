<?php

namespace Tests\Feature\Security;

use App\Models\Address;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Notification;
use App\Models\Quotation;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IDORTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $worker;

    private User $otherCustomer;

    private User $otherWorker;

    private ServiceRequest $request;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->customer()->create();
        $this->worker = User::factory()->worker()->create();
        $this->otherCustomer = User::factory()->customer()->create();
        $this->otherWorker = User::factory()->worker()->create();

        $this->request = ServiceRequest::factory()
            ->for($this->owner, 'customer')
            ->status('accepted')
            ->create(['worker_id' => $this->worker->id]);
    }

    public function test_other_customer_cannot_touch_a_foreign_request(): void
    {
        $this->actingAs($this->otherCustomer);

        $this->get(route('customer.requests.show', $this->request))->assertForbidden();
        $this->get(route('customer.messages.show', $this->request))->assertForbidden();
        $this->post(route('customer.messages.store', $this->request), ['body' => 'hi'])->assertForbidden();
        $this->post(route('customer.requests.cancel', $this->request))->assertForbidden();
        $this->post(route('customer.reviews.store', $this->request), ['rating' => 5])->assertForbidden();
        $this->post(route('customer.payments.pay', $this->request))->assertForbidden();
    }

    public function test_other_customer_cannot_respond_to_a_foreign_quotation(): void
    {
        $quotation = Quotation::factory()->create([
            'service_request_id' => $this->request->id,
            'worker_id' => $this->worker->id,
        ]);

        $this->actingAs($this->otherCustomer);
        $this->post(route('customer.quotations.accept', $quotation))->assertForbidden();
        $this->post(route('customer.quotations.reject', $quotation))->assertForbidden();
        $this->assertSame('pending', $quotation->fresh()->status);
    }

    public function test_other_customer_cannot_delete_a_foreign_address(): void
    {
        $address = Address::factory()->for($this->owner, 'user')->create();

        $this->actingAs($this->otherCustomer)
            ->delete(route('customer.addresses.destroy', $address))
            ->assertForbidden();

        $this->assertModelExists($address);
    }

    public function test_other_worker_cannot_touch_a_foreign_job(): void
    {
        $this->actingAs($this->otherWorker);

        $this->get(route('worker.jobs.show', $this->request))->assertForbidden();
        $this->post(route('worker.jobs.accept', $this->request))->assertForbidden();
        $this->post(route('worker.jobs.decline', $this->request))->assertForbidden();
        $this->post(route('worker.jobs.status', $this->request), ['status' => 'completed'])->assertForbidden();
        $this->post(route('worker.jobs.quotation', $this->request), ['base_price' => 50])->assertForbidden();
        $this->post(route('worker.messages.store', $this->request), ['body' => 'hi'])->assertForbidden();
    }

    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        $notification = Notification::create([
            'user_id' => $this->owner->id,
            'type' => 'test',
            'title' => 't',
            'message' => 'm',
        ]);

        $this->actingAs($this->otherCustomer)
            ->post(route('notifications.read', $notification->id))
            ->assertNotFound();

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_non_participant_cannot_download_request_or_message_attachments(): void
    {
        $this->request->update(['problem_images' => ['requests/secret.jpg']]);
        $conversation = Conversation::create([
            'service_request_id' => $this->request->id,
            'customer_id' => $this->owner->id,
            'worker_id' => $this->worker->id,
        ]);
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $this->owner->id,
            'body' => 'see photo',
            'attachments' => ['messages/secret.jpg'],
        ]);

        $this->actingAs($this->otherWorker);
        $this->get(route('attachments.request', [$this->request, 'problem', 0]))->assertForbidden();
        $this->get(route('attachments.message', [$message, 0]))->assertForbidden();
    }
}
