<?php

namespace App\Policies;

use App\Models\Message;
use App\Models\User;

class MessagePolicy
{
    /**
     * A user may view a message (and its attachments) if they are a party to the
     * parent request, or an admin operating the support/dispute workspace.
     */
    public function view(User $user, Message $message): bool
    {
        $request = $message->conversation?->request;

        if ($request === null) {
            return false;
        }

        return $user->role === 'admin'
            || $user->id === $request->customer_id
            || ($request->worker_id !== null && $user->id === $request->worker_id);
    }
}
