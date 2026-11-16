<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ServiceRequest extends Model
{
    use HasFactory;

    protected $fillable = ['reference', 'customer_id', 'worker_id', 'service_category_id', 'address_id', 'problem_description', 'problem_images', 'preferred_date', 'preferred_time', 'is_emergency', 'status', 'quoted_total', 'final_total', 'payment_status', 'completed_at', 'completion_notes', 'completion_images'];

    protected function casts(): array
    {
        return ['problem_images' => 'array', 'preferred_date' => 'date', 'is_emergency' => 'boolean', 'quoted_total' => 'decimal:2', 'final_total' => 'decimal:2', 'completed_at' => 'datetime', 'completion_images' => 'array'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function appointment(): HasOne
    {
        return $this->hasOne(Appointment::class);
    }

    public function rescheduleRequests(): HasMany
    {
        return $this->hasMany(RescheduleRequest::class);
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    /**
     * The amount this request is (or will be) charged at: the completion
     * snapshot if it exists, otherwise the accepted-quotation total. Returns
     * null when neither has been set — callers must not treat that as zero.
     */
    public function lockedAmount(): ?string
    {
        return $this->final_total ?? $this->quoted_total;
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }

    public function conversation(): HasOne
    {
        return $this->hasOne(Conversation::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return str_replace('_', ' ', ucwords($this->status, '_'));
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'completed' => 'success','cancelled','rejected' => 'danger','pending' => 'warning','accepted','scheduled','on_the_way','in_progress' => 'info',default => 'muted'
        };
    }
}
