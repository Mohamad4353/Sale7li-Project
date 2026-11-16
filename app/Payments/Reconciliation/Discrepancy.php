<?php

namespace App\Payments\Reconciliation;

/**
 * One detected inconsistency in the financial records. Purely descriptive —
 * building it never changes anything.
 */
final readonly class Discrepancy
{
    public function __construct(
        public string $code,
        public ?int $paymentId = null,
        public ?string $reference = null,
        public ?string $customer = null,
        public ?string $worker = null,
        public ?string $expected = null,
        public ?string $actual = null,
    ) {}

    /** Localised, human-readable explanation of this discrepancy code. */
    public function label(): string
    {
        return __('payments.recon_code_'.$this->code);
    }
}
