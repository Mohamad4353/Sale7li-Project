<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    /**
     * Who may see a payment and its proof file: the customer who made it, or an
     * admin reviewing it. The assigned worker never sees the customer's proof.
     */
    public function view(User $user, Payment $payment): bool
    {
        return $user->id === $payment->customer_id
            || $user->role === 'admin';
    }

    /**
     * Who may approve or reject a pending WHISH payment: an admin who is not the
     * payer. A customer can never verify a payment, not even their own.
     */
    public function verify(User $user, Payment $payment): bool
    {
        return $user->role === 'admin'
            && $user->id !== $payment->customer_id;
    }
}
