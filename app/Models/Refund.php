<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An internal, application-level refund of a captured Payment.
 *
 * `amount`, `type` and `status` are financial facts written only from
 * server-derived values inside RefundService — never from request input. The
 * fields are listed in $fillable so the service's explicit create() array
 * works; do not populate them from user data.
 *
 * A refund never changes the Payment it belongs to: the payment keeps its
 * frozen amount / commission_rate / commission_amount / worker_net and its
 * `paid` status. This row alone represents the reversal.
 */
class Refund extends Model
{
    use HasFactory;

    public const TYPE_FULL = 'full';

    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'payment_id', 'amount', 'type', 'status', 'reason', 'processed_by', 'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'processed_at' => 'datetime',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }
}
