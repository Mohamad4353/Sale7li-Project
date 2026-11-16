<?php

namespace App\Policies;

use App\Models\RescheduleRequest;
use App\Models\User;

class RescheduleRequestPolicy
{
    /** The professional assigned to the parent request may accept or decline a proposal. */
    public function respond(User $user, RescheduleRequest $rescheduleRequest): bool
    {
        return $rescheduleRequest->request->worker_id !== null
            && $user->id === $rescheduleRequest->request->worker_id;
    }
}
