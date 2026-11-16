<?php

namespace Tests\Feature\Messaging;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * P14-24-A — the conversation inbox shows an avatar for the counterpart. It
 * reuses <x-avatar> off the already eager-loaded customer/worker relations, so
 * no per-row query is added.
 */
class InboxAvatarTest extends TestCase
{
    use RefreshDatabase;

    private function conversationBetween(User $customer, User $worker): Conversation
    {
        $request = ServiceRequest::factory()->for($customer, 'customer')->status('accepted')->create([
            'worker_id' => $worker->id,
        ]);
        $conversation = Conversation::create([
            'service_request_id' => $request->id,
            'customer_id' => $customer->id,
            'worker_id' => $worker->id,
        ]);
        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $worker->id,
            'body' => 'hello there',
        ]);

        return $conversation;
    }

    public function test_inbox_renders_an_avatar_for_each_counterpart(): void
    {
        $customer = User::factory()->customer()->create();
        $this->conversationBetween($customer, User::factory()->worker()->create(['name' => 'Wilma Wire']));
        $this->conversationBetween($customer, User::factory()->worker()->create(['name' => 'Pablo Pipe']));

        $html = $this->actingAs($customer)->get(route('messages.index'))->assertOk()
            ->assertSee('Wilma Wire')
            ->assertSee('Pablo Pipe')
            ->getContent();

        // <x-avatar> renders initials for each counterpart.
        $this->assertStringContainsString('WW', $html);
        $this->assertStringContainsString('PP', $html);
    }

    public function test_inbox_query_count_does_not_grow_with_conversations(): void
    {
        $customer = User::factory()->customer()->create();
        foreach (range(1, 5) as $i) {
            $this->conversationBetween($customer, User::factory()->worker()->create(['name' => "Worker Number {$i}"]));
        }

        // Warm auth/session, then measure.
        $this->actingAs($customer)->get(route('messages.index'))->assertOk();

        DB::enableQueryLog();
        $this->actingAs($customer)->get(route('messages.index'))->assertOk();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Eager-loaded counterparts => a small constant number of queries,
        // nowhere near one-per-row.
        $this->assertLessThan(15, $count, "Inbox ran {$count} queries — possible N+1.");
    }

    public function test_inbox_empty_state_still_renders(): void
    {
        $this->actingAs(User::factory()->customer()->create())
            ->get(route('messages.index'))->assertOk()
            ->assertSee(__('messages.inbox.empty_title'));
    }
}
