<?php

namespace Tests\Feature\Messaging;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Phase 25 — the messaging attachment gallery. A read-only presentation layer on
 * messages/show: it re-lists the photos already exchanged in the thread and links
 * each one through the existing, already-authorized `attachments.message` route.
 * No new storage, no new authorization surface, no realtime, no migration.
 */
class MessageAttachmentGalleryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{customer: User, worker: User, request: ServiceRequest, conversation: Conversation}
     */
    private function thread(): array
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

        return compact('customer', 'worker', 'request', 'conversation');
    }

    /** @param  list<string>  $attachments */
    private function message(Conversation $conversation, int $senderId, array $attachments, string $body = 'see photo'): Message
    {
        return Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $senderId,
            'body' => $body,
            'attachments' => $attachments,
        ]);
    }

    /* ---------------------------- rendering ---------------------------- */

    public function test_the_gallery_lists_every_thread_attachment_for_each_participant(): void
    {
        ['customer' => $customer, 'worker' => $worker, 'request' => $request, 'conversation' => $conversation] = $this->thread();
        $a = $this->message($conversation, $customer->id, ['messages/one.jpg']);
        $b = $this->message($conversation, $worker->id, ['messages/two.jpg', 'messages/three.jpg']);

        foreach ([$customer, $worker] as $viewer) {
            $res = $this->actingAs($viewer)->get(route('messages.show', $request))->assertOk();
            $res->assertSee(__('messages.gallery.title'));
            $res->assertSee(route('attachments.message', [$a, 0]), false);
            $res->assertSee(route('attachments.message', [$b, 0]), false);
            $res->assertSee(route('attachments.message', [$b, 1]), false);
        }
    }

    public function test_the_gallery_shows_the_photo_count(): void
    {
        ['customer' => $customer, 'request' => $request, 'conversation' => $conversation] = $this->thread();
        $this->message($conversation, $customer->id, ['messages/a.jpg', 'messages/b.jpg']);
        $this->message($conversation, $customer->id, ['messages/c.jpg']);

        $this->actingAs($customer)->get(route('messages.show', $request))
            ->assertOk()
            ->assertSeeInOrder([__('messages.gallery.title'), '(3)']);
    }

    public function test_no_gallery_section_when_the_thread_has_no_attachments(): void
    {
        ['customer' => $customer, 'request' => $request, 'conversation' => $conversation] = $this->thread();
        $this->message($conversation, $customer->id, [], 'just text');
        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $customer->id,
            'body' => 'no attachments key at all',
        ]);

        $this->actingAs($customer)->get(route('messages.show', $request))
            ->assertOk()
            ->assertDontSee(__('messages.gallery.title'));
    }

    public function test_attachments_are_listed_newest_message_first(): void
    {
        ['customer' => $customer, 'worker' => $worker, 'request' => $request, 'conversation' => $conversation] = $this->thread();
        $old = $this->message($conversation, $customer->id, ['messages/old.jpg']);
        $mid = $this->message($conversation, $worker->id, ['messages/mid.jpg']);
        $new = $this->message($conversation, $customer->id, ['messages/new.jpg']);

        $html = $this->actingAs($customer)->get(route('messages.show', $request))->assertOk()->getContent();
        // Scope the ordering check to the gallery — the chat thread above it
        // renders the same links in chronological (oldest-first) order.
        $gallery = substr($html, (int) strpos($html, __('messages.gallery.title')));

        $posNew = strpos($gallery, route('attachments.message', [$new, 0]));
        $posMid = strpos($gallery, route('attachments.message', [$mid, 0]));
        $posOld = strpos($gallery, route('attachments.message', [$old, 0]));

        $this->assertNotFalse($posNew);
        $this->assertLessThan($posMid, $posNew, 'newest attachment must come first');
        $this->assertLessThan($posOld, $posMid, 'older attachment must come last');
    }

    public function test_the_gallery_does_not_leak_the_stored_file_path(): void
    {
        ['customer' => $customer, 'request' => $request, 'conversation' => $conversation] = $this->thread();
        $this->message($conversation, $customer->id, ['messages/secret-blob-name.webp']);

        $this->actingAs($customer)->get(route('messages.show', $request))
            ->assertOk()
            ->assertDontSee('secret-blob-name');
    }

    public function test_the_gallery_photo_has_localized_alt_text(): void
    {
        ['customer' => $customer, 'request' => $request, 'conversation' => $conversation] = $this->thread();
        $this->message($conversation, $customer->id, ['messages/a.jpg']);

        $this->actingAs($customer)->get(route('messages.show', $request))
            ->assertOk()
            ->assertSee('alt="'.e(__('messages.gallery.alt')).'"', false);
    }

    /* ------------------------ authorization / IDOR ------------------------ */

    public function test_a_participant_can_stream_a_gallery_photo(): void
    {
        Storage::fake('local');
        ['customer' => $customer, 'conversation' => $conversation] = $this->thread();
        Storage::disk('local')->put('messages/photo.jpg', 'binary-image-bytes');
        $msg = $this->message($conversation, $customer->id, ['messages/photo.jpg']);

        $this->actingAs($customer)
            ->get(route('attachments.message', [$msg, 0]))
            ->assertOk();
    }

    public function test_a_non_participant_cannot_open_the_thread_or_its_gallery_photos(): void
    {
        ['customer' => $customer, 'request' => $request, 'conversation' => $conversation] = $this->thread();
        $msg = $this->message($conversation, $customer->id, ['messages/x.jpg']);
        $stranger = User::factory()->customer()->create();

        $this->actingAs($stranger)->get(route('messages.show', $request))->assertForbidden();
        $this->actingAs($stranger)->get(route('attachments.message', [$msg, 0]))->assertForbidden();
    }

    public function test_an_out_of_range_gallery_index_is_not_found(): void
    {
        ['customer' => $customer, 'conversation' => $conversation] = $this->thread();
        $msg = $this->message($conversation, $customer->id, ['messages/only-one.jpg']);

        $this->actingAs($customer)
            ->get(route('attachments.message', [$msg, 7]))
            ->assertNotFound();
    }

    public function test_a_guest_cannot_reach_the_thread(): void
    {
        ['request' => $request] = $this->thread();

        $this->get(route('messages.show', $request))->assertRedirect(route('login'));
    }

    /* ---------------------------- regression ---------------------------- */

    public function test_the_chat_thread_and_send_form_still_render_alongside_the_gallery(): void
    {
        ['customer' => $customer, 'request' => $request, 'conversation' => $conversation] = $this->thread();
        $this->message($conversation, $customer->id, ['messages/a.jpg'], 'here is the photo');

        $res = $this->actingAs($customer)->get(route('messages.show', $request))->assertOk();
        $res->assertSee('here is the photo');
        $res->assertSee('chatPoll(', false);
        $res->assertSee('action="'.route('customer.messages.store', $request).'"', false);
        $res->assertSee('name="attachments[]"', false);
    }

    public function test_the_gallery_does_not_add_per_photo_queries(): void
    {
        ['customer' => $customer, 'request' => $request, 'conversation' => $conversation] = $this->thread();
        $this->message($conversation, $customer->id, ['messages/a.jpg']);

        DB::enableQueryLog();
        $this->actingAs($customer)->get(route('messages.show', $request))->assertOk();
        $few = count(DB::getQueryLog());

        for ($i = 0; $i < 6; $i++) {
            $this->message($conversation, $customer->id, ["messages/x-{$i}.jpg", "messages/x-{$i}-b.jpg"]);
        }
        DB::flushQueryLog();
        $this->actingAs($customer)->get(route('messages.show', $request))->assertOk();
        $many = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame($few, $many, "gallery ran {$few} vs {$many} queries — it must be one flat query regardless of photo count");
    }

    public function test_the_gallery_is_localized_in_arabic_without_leaking_keys(): void
    {
        ['customer' => $customer, 'request' => $request, 'conversation' => $conversation] = $this->thread();
        $this->message($conversation, $customer->id, ['messages/a.jpg']);

        $res = $this->actingAs($customer)->withSession(['locale' => 'ar'])
            ->get(route('messages.show', $request))->assertOk();

        $res->assertSee(__('messages.gallery.title', [], 'ar'), false);
        $res->assertDontSee('messages.gallery.');
    }
}
