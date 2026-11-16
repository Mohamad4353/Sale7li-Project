<?php

namespace Tests\Feature\Messaging;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessagingPollingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{customer: User, worker: User, request: ServiceRequest, conversation: Conversation}
     */
    private function threadWithMessages(int $count): array
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
        for ($i = 1; $i <= $count; $i++) {
            Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $i % 2 === 0 ? $customer->id : $worker->id,
                'body' => "message #{$i}",
            ]);
        }

        return compact('customer', 'worker', 'request', 'conversation');
    }

    public function test_conversation_view_paginates_and_shows_the_newest_messages_first_page(): void
    {
        ['customer' => $customer, 'request' => $request] = $this->threadWithMessages(45);

        $response = $this->actingAs($customer)->get(route('messages.show', $request))->assertOk();

        // Newest present, oldest paged out.
        $response->assertSee('message #45');
        $response->assertDontSee('message #1</div>', false);
        // A "load older" affordance exists.
        $response->assertSee(__('messages.chat.load_older'));
    }

    public function test_older_messages_remain_accessible_through_pagination(): void
    {
        ['customer' => $customer, 'request' => $request] = $this->threadWithMessages(45);

        $this->actingAs($customer)
            ->get(route('messages.show', [$request, 'page' => 2]))
            ->assertOk()
            ->assertSee('message #1');
    }

    public function test_poll_returns_only_messages_after_the_given_id(): void
    {
        ['customer' => $customer, 'worker' => $worker, 'request' => $request, 'conversation' => $conversation] = $this->threadWithMessages(5);
        $latestBefore = $conversation->messages()->max('id');

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $worker->id,
            'body' => 'a brand new reply',
        ]);

        $data = $this->actingAs($customer)
            ->getJson(route('messages.poll', $request).'?after='.$latestBefore)
            ->assertOk()
            ->json();

        $this->assertStringContainsString('a brand new reply', $data['html']);
        $this->assertStringNotContainsString('message #5', $data['html']);
        $this->assertGreaterThan($latestBefore, $data['last_id']);
    }

    public function test_poll_does_not_duplicate_already_delivered_messages(): void
    {
        ['customer' => $customer, 'request' => $request, 'conversation' => $conversation] = $this->threadWithMessages(3);
        $latest = $conversation->messages()->max('id');

        $data = $this->actingAs($customer)
            ->getJson(route('messages.poll', $request).'?after='.$latest)
            ->assertOk()
            ->json();

        $this->assertSame('', trim($data['html']));
        $this->assertSame($latest, $data['last_id']);
    }

    public function test_poll_marks_incoming_messages_read_without_re_touching_already_read_ones(): void
    {
        ['customer' => $customer, 'worker' => $worker, 'request' => $request, 'conversation' => $conversation] = $this->threadWithMessages(2);

        $firstIncoming = $conversation->messages()->where('sender_id', $worker->id)->orderBy('id')->first();
        $firstIncoming->update(['read_at' => now()->subDay()]);
        $readStamp = $firstIncoming->fresh()->read_at;

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $worker->id,
            'body' => 'newest from worker',
        ]);

        $this->actingAs($customer)->getJson(route('messages.poll', $request).'?after=0')->assertOk();

        // Already-read message keeps its original timestamp.
        $this->assertEquals($readStamp, $firstIncoming->fresh()->read_at);
        // The new one is now read.
        $this->assertNotNull($conversation->messages()->where('body', 'newest from worker')->first()->read_at);
    }

    public function test_poll_authorization_matches_the_conversation_policy(): void
    {
        ['request' => $request] = $this->threadWithMessages(2);

        $this->getJson(route('messages.poll', $request))->assertUnauthorized();

        $this->actingAs(User::factory()->customer()->create())
            ->getJson(route('messages.poll', $request))
            ->assertForbidden();
    }

    public function test_sending_a_message_still_works_and_is_not_duplicated(): void
    {
        ['customer' => $customer, 'request' => $request, 'conversation' => $conversation] = $this->threadWithMessages(1);

        $this->actingAs($customer)
            ->post(route('customer.messages.store', $request), ['body' => 'single send'])
            ->assertRedirect();

        $this->assertSame(1, $conversation->messages()->where('body', 'single send')->count());
    }
}
