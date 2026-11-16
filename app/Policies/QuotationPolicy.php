<?php

namespace App\Policies;

use App\Models\Quotation;
use App\Models\User;

class QuotationPolicy
{
    /** The customer who owns the parent request may accept or reject a quotation. */
    public function respond(User $user, Quotation $quotation): bool
    {
        return $user->id === $quotation->request->customer_id;
    }
}
