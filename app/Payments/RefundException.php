<?php

namespace App\Payments;

use DomainException;

/**
 * A refund was refused for a business reason (not a bug). The controller maps
 * `$e->reasonKey` to a localised flash message; the message string is the same
 * key so `getMessage()` stays useful in logs.
 */
final class RefundException extends DomainException
{
    private function __construct(public readonly string $reasonKey)
    {
        parent::__construct($reasonKey);
    }

    public static function notPaid(): self
    {
        return new self('payments.refund_error_not_paid');
    }

    public static function alreadyRefunded(): self
    {
        return new self('payments.refund_error_already_refunded');
    }

    public static function nothingToRefund(): self
    {
        return new self('payments.refund_error_nothing');
    }

    public static function exceedsPayment(): self
    {
        return new self('payments.refund_error_exceeds');
    }
}
