<?php

namespace Tests\Feature\Messaging;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\WorkerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MessagingInboxTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{customer: User, worker: User, request: ServiceRequest, conversation: Conversation}
     */
    private function conversation(array $messages = []): array
    {
        $customer = User::factory()->customer()->create();
        $worker = User::factory()->worker()->create();
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('accepted')->create([
            'worker_id' => $worker->id,
        ]);
        $conversation = Conversation::create([
            'service_request_id' => $request->id,
            'customer_id' => $customer->id,
            'worker_id' => $worker->id,
        ]);
        foreach ($messages as $m) {
            Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $m['from'] === 'customer' ? $customer->id : $worker->id,
                'body' => $m['body'],
                'read_at' => $m['read'] ?? null,
            ]);
        }

        return compact('customer', 'worker', 'request', 'conversation');
    }

    public function test_inbox_is_accessible_to_authenticated_users(): void
    {
        $this->get(route('messages.index'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->customer()->create())
            ->get(route('messages.index'))->assertOk();
    }

    public function test_customer_sees_only_their_own_conversations(): void
    {
        ['customer' => $customer, 'request' => $request] = $this->conversation([
            ['from' => 'worker', 'body' => 'Hello from your pro'],
        ]);
        $otherRequest = ServiceRequest::factory()->status('accepted')->create([
            'worker_id' => User::factory()->worker()->create()->id,
        ]);
        Conversation::create([
            'service_request_id' => $otherRequest->id,
            'customer_id' => $otherRequest->customer_id,
            'worker_id' => $otherRequest->worker_id,
        ]);

        $this->actingAs($customer)
            ->get(route('messages.index'))
            ->assertOk()
            ->assertSee($request->reference)
            ->assertDontSee($otherRequest->reference);
    }

    public function test_worker_sees_their_conversations(): void
    {
        ['worker' => $worker, 'request' => $request] = $this->conversation([
            ['from' => 'customer', 'body' => 'When can you come?'],
        ]);

        $this->actingAs($worker)
            ->get(route('messages.index'))
            ->assertOk()
            ->assertSee($request->reference)
            ->assertSee('When can you come?');
    }

    public function test_unread_indicator_and_count_are_shown_for_incoming_unread_messages(): void
    {
        ['customer' => $customer] = $this->conversation([
            ['from' => 'worker', 'body' => 'msg one'],
            ['from' => 'worker', 'body' => 'msg two'],
            ['from' => 'customer', 'body' => 'my own message'],
        ]);

        $this->actingAs($customer)
            ->get(route('messages.index'))
            ->assertOk()
            ->assertSee(__('messages.inbox.unread_count', ['count' => 2]));
    }

    public function test_opening_a_conversation_marks_incoming_messages_read(): void
    {
        ['customer' => $customer, 'worker' => $worker, 'request' => $request, 'conversation' => $conversation] = $this->conversation([
            ['from' => 'worker', 'body' => 'unread for the customer'],
        ]);

        $this->actingAs($customer)->get(route('messages.show', $request))->assertOk();

        $this->assertNotNull(
            $conversation->messages()->where('sender_id', $worker->id)->first()->read_at
        );
        // The inbox no longer shows an unread count for that conversation.
        $this->actingAs($customer)->get(route('messages.index'))
            ->assertDontSee(__('messages.inbox.unread_count', ['count' => 1]));
    }

    public function test_conversations_are_ordered_by_latest_message_descending(): void
    {
        $customer = User::factory()->customer()->create();

        $old = $this->conversationFor($customer, 'SL-OLD', now()->subDays(3));
        $recent = $this->conversationFor($customer, 'SL-NEW', now()->subMinutes(5));

        $this->actingAs($customer)
            ->get(route('messages.index'))
            ->assertOk()
            ->assertSeeInOrder(['SL-NEW', 'SL-OLD']);
    }

    private function conversationFor(User $customer, string $reference, Carbon $lastAt): Conversation
    {
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('accepted')->create([
            'reference' => $reference,
            'worker_id' => User::factory()->worker()->create()->id,
        ]);
        $conversation = Conversation::create([
            'service_request_id' => $request->id,
            'customer_id' => $customer->id,
            'worker_id' => $request->worker_id,
        ]);
        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $request->worker_id,
            'body' => 'ping '.$reference,
        ])->forceFill(['created_at' => $lastAt, 'updated_at' => $lastAt])->save();

        return $conversation;
    }

    public function test_empty_inbox_shows_an_empty_state(): void
    {
        $this->actingAs(User::factory()->worker()->create())
            ->get(route('messages.index'))
            ->assertOk()
            ->assertSee(__('messages.inbox.empty_title'));
    }

    public function test_messages_nav_link_is_present_for_customer_and_worker(): void
    {
        $this->actingAs(User::factory()->customer()->create())
            ->get(route('customer.dashboard'))
            ->assertSee('href="'.route('messages.index').'"', false);

        $worker = User::factory()->worker()->create();
        WorkerProfile::factory()->for($worker, 'user')->create();
        $this->actingAs($worker)
            ->get(route('worker.dashboard'))
            ->assertSee('href="'.route('messages.index').'"', false);
    }

    public function test_direct_conversation_access_has_a_working_role_aware_back_link(): void
    {
        ['customer' => $customer, 'worker' => $worker, 'request' => $request] = $this->conversation([
            ['from' => 'worker', 'body' => 'hi'],
        ]);

        // No referrer — simulates opening from a notification or a bookmark.
        $this->actingAs($customer)->get(route('messages.show', $request))
            ->assertOk()
            ->assertSee('href="'.route('messages.index').'"', false);

        $this->actingAs($worker)->get(route('messages.show', $request))
            ->assertOk()
            ->assertSee('href="'.route('messages.index').'"', false);
    }
}
