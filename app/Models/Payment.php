<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use HasFactory;

    /**
     * `amount`, `status`, `worker_id`, `commission_rate`, `commission_amount`,
     * `worker_net`, `paid_at`, `verified_at` and `verified_by` are only ever
     * written from server-derived values (see PaymentCaptureService and the
     * admin verification path) — never from customer request input. The
     * customer only ever supplies `whish_reference` and `payment_proof_path`
     * on a pending WHISH submission.
     */
    protected $fillable = [
        'service_request_id', 'customer_id', 'worker_id', 'transaction_reference',
        'payment_method', 'whish_reference', 'payment_proof_path',
        'amount', 'commission_rate', 'commission_amount', 'worker_net',
        'currency', 'status', 'paid_at', 'submitted_at',
        'verified_at', 'verified_by', 'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'commission_rate' => 'decimal:4',
            'commission_amount' => 'decimal:2',
            'worker_net' => 'decimal:2',
            'paid_at' => 'datetime',
            'submitted_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    /** True for a WHISH submission still awaiting admin verification. */
    public function isWhishPending(): bool
    {
        return $this->payment_method === 'whish' && $this->status === 'pending';
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class, 'service_request_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
