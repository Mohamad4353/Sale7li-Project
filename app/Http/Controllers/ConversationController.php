<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMessageRequest;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    /** Newest-first page size for a conversation thread. */
    private const PAGE_SIZE = 20;

    /**
     * Cross-request inbox for the signed-in user: every conversation they are a
     * party to, newest activity first, with an unread count. Reuses MessagePolicy
     * scoping implicitly — the query is limited to conversations the user owns.
     */
    public function index()
    {
        $userId = auth()->id();

        $conversations = Conversation::query()
            ->where(fn ($q) => $q->where('customer_id', $userId)->orWhere('worker_id', $userId))
            ->with([
                'request:id,reference,service_category_id',
                'request.category:id,name,name_ar,slug',
                'customer:id,name',
                'worker:id,name',
                'latestMessage',
            ])
            ->withCount(['messages as unread_count' => fn ($q) => $q
                ->where('sender_id', '!=', $userId)
                ->whereNull('read_at')])
            ->addSelect(['last_activity' => Message::select('created_at')
                ->whereColumn('conversation_id', 'conversations.id')
                ->latest()
                ->limit(1)])
            ->orderByRaw('last_activity IS NULL')
            ->orderByDesc('last_activity')
            ->paginate(15);

        return view('messages.index', compact('conversations'));
    }

    public function show(ServiceRequest $serviceRequest)
    {
        $this->authorize('message', $serviceRequest);
        abort_unless($serviceRequest->worker_id, 422);

        $conversation = Conversation::firstOrCreate(
            ['service_request_id' => $serviceRequest->id],
            ['customer_id' => $serviceRequest->customer_id, 'worker_id' => $serviceRequest->worker_id],
        );

        $conversation->load('request.category');

        // Newest-first pagination; the view flips the current page to read
        // chronologically. "Load older" walks to the next (older) page.
        $messages = $conversation->messages()
            ->with('sender:id,name')
            ->orderByDesc('id')
            ->paginate(self::PAGE_SIZE);

        $conversation->messages()
            ->where('sender_id', '!=', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        // The poller only ever fetches messages created after this page load,
        // regardless of which page is shown — so it can never re-append or
        // duplicate anything already on the page.
        $lastId = (int) $conversation->messages()->max('id');

        // Phase 25: a flat, read-only list of every photo already exchanged in
        // this thread, newest message first. One query regardless of count; each
        // item links through the existing authorized `attachments.message` route,
        // so there is no new storage path or authorization surface here.
        $attachments = $conversation->messages()
            ->whereNotNull('attachments')
            ->where('attachments', '!=', '[]')
            ->orderByDesc('id')
            ->get(['id', 'attachments', 'created_at'])
            ->flatMap(fn (Message $m) => collect($m->attachments ?? [])->keys()->map(fn ($i) => [
                'url' => route('attachments.message', ['message' => $m->id, 'index' => $i]),
                'date' => $m->created_at,
            ]))
            ->values();

        return view('messages.show', compact('conversation', 'messages', 'lastId', 'attachments'));
    }

    /**
     * JSON delta for the Alpine poller: messages with id greater than `after`,
     * rendered server-side (one source of bubble markup). Incoming messages are
     * marked read here because the user is actively viewing the thread. No new
     * messages are ever created by this endpoint.
     */
    public function poll(Request $request, ServiceRequest $serviceRequest)
    {
        $this->authorize('message', $serviceRequest);

        $afterId = (int) $request->query('after', 0);
        $conversation = $serviceRequest->conversation;

        if ($conversation === null) {
            return response()->json(['last_id' => $afterId, 'html' => '']);
        }

        $new = $conversation->messages()
            ->with('sender:id,name')
            ->where('id', '>', $afterId)
            ->orderBy('id')
            ->limit(100)
            ->get();

        $conversation->messages()
            ->where('id', '>', $afterId)
            ->where('sender_id', '!=', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'last_id' => (int) ($new->max('id') ?? $afterId),
            'html' => $new->map(fn (Message $m) => view('messages._bubble', ['message' => $m])->render())->implode(''),
        ]);
    }

    public function store(StoreMessageRequest $request, ServiceRequest $serviceRequest)
    {
        abort_unless($serviceRequest->worker_id, 422);

        $data = $request->validated();

        $conversation = Conversation::firstOrCreate(
            ['service_request_id' => $serviceRequest->id],
            ['customer_id' => $serviceRequest->customer_id, 'worker_id' => $serviceRequest->worker_id],
        );

        $paths = [];
        foreach ($request->file('attachments', []) as $file) {
            $paths[] = $file->store('messages', 'local');
        }

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => auth()->id(),
            'body' => $data['body'],
            'attachments' => $paths,
        ]);

        return back()->with('success', __('messages.flash.message_sent'));
    }
}
