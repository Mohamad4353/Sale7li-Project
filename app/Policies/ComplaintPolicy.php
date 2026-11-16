<?php

namespace App\Policies;

use App\Models\Complaint;
use App\Models\User;

class ComplaintPolicy
{
    /**
     * Who may read a single complaint: an admin, the person who filed it, or the
     * professional currently assigned to the request it is about. Everyone else
     * (including a different customer or an unrelated worker) is denied — the
     * complaint id in the URL is never trusted on its own.
     */
    public function view(User $user, Complaint $complaint): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->id === $complaint->reporter_id) {
            return true;
        }

        $request = $complaint->request;

        return $request !== null
            && $request->worker_id !== null
            && $user->id === $request->worker_id;
    }
}
