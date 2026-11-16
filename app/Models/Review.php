<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Review extends Model
{
    use HasFactory;

    protected $fillable = ['service_request_id', 'customer_id', 'worker_id', 'rating', 'comment', 'photos'];

    protected function casts(): array
    {
        return ['photos' => 'array', 'rating' => 'integer'];
    }

    /**
     * First name only — reviews are public on the marketplace, so the
     * reviewer's full identity is never exposed.
     */
    public function getCustomerDisplayNameAttribute(): string
    {
        $name = trim((string) $this->customer?->name);

        return $name === '' ? __('reviews.anonymous') : Str::before($name, ' ');
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
}
