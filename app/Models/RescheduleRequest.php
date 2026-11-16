<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RescheduleRequest extends Model
{
    use HasFactory;

    protected $fillable = ['service_request_id', 'proposed_date', 'proposed_time', 'reason', 'status', 'decline_reason', 'responded_at'];

    protected function casts(): array
    {
        return ['proposed_date' => 'date', 'responded_at' => 'datetime'];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class, 'service_request_id');
    }
}
