<?php

namespace App\Payments;

/**
 * The deterministic outcome of a commission calculation. Amounts are exposed
 * both as integer cents (for further arithmetic / assertions) and as canonical
 * "x.xx" strings (for persistence into decimal(10,2) columns). The `rate` is the
 * 4-decimal fraction that was applied — this is the value Step 2 will freeze
 * onto the payment row.
 */
final readonly class CommissionResult
{
    public function __construct(
        public int $grossCents,
        public string $rate,
        public int $commissionCents,
        public int $netCents,
    ) {}

    public function grossAmount(): string
    {
        return Money::toDecimalString($this->grossCents);
    }

    public function commissionAmount(): string
    {
        return Money::toDecimalString($this->commissionCents);
    }

    public function workerNetAmount(): string
    {
        return Money::toDecimalString($this->netCents);
    }

    /** @return array{gross:string, rate:string, commission:string, worker_net:string} */
    public function toArray(): array
    {
        return [
            'gross' => $this->grossAmount(),
            'rate' => $this->rate,
            'commission' => $this->commissionAmount(),
            'worker_net' => $this->workerNetAmount(),
        ];
    }
}
