<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkerProfile extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'bio', 'bio_ar', 'photo', 'experience_years', 'verification_status', 'rating_average', 'rating_count', 'completed_jobs', 'completion_rate', 'cancellation_rate', 'service_radius_km'];

    protected function casts(): array
    {
        return ['rating_average' => 'decimal:2', 'completion_rate' => 'decimal:2', 'cancellation_rate' => 'decimal:2', 'service_radius_km' => 'decimal:2'];
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('verification_status', 'verified');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(WorkerService::class, 'worker_id', 'user_id');
    }

    public function availability(): HasMany
    {
        return $this->hasMany(WorkerAvailability::class, 'worker_id', 'user_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'worker_id', 'user_id');
    }

    public function portfolioItems(): HasMany
    {
        return $this->hasMany(WorkerPortfolioItem::class, 'worker_id', 'user_id');
    }

    public function hasReviews(): bool
    {
        return $this->rating_count > 0;
    }

    public function hasProfileBasics(): bool
    {
        return filled($this->bio);
    }

    public function hasPhoto(): bool
    {
        return filled($this->photo);
    }

    public function hasActiveService(): bool
    {
        return $this->services()->where('is_active', true)->exists();
    }

    public function hasPublishedAvailability(): bool
    {
        return $this->availability()->where('is_available', true)->exists();
    }

    /**
     * The go-live checklist, as [key => done]. Order is the order shown to the
     * professional on their dashboard.
     *
     * @return array<string, bool>
     */
    public function completenessItems(): array
    {
        return [
            'profile' => $this->hasProfileBasics(),
            'photo' => $this->hasPhoto(),
            'services' => $this->hasActiveService(),
            'availability' => $this->hasPublishedAvailability(),
        ];
    }

    /** Whole-number percentage of the go-live checklist that is complete. */
    public function completeness(): int
    {
        $items = $this->completenessItems();

        return (int) round(count(array_filter($items)) / count($items) * 100);
    }

    /**
     * Whether this professional is currently eligible to appear in public
     * marketplace discovery. Mirrors User::scopeVerifiedProfessional exactly.
     * Availability and a photo are encouraged but NOT required for visibility.
     */
    public function canGoLive(): bool
    {
        return $this->user->role === 'worker'
            && $this->user->status === 'active'
            && $this->verification_status === 'verified'
            && $this->hasActiveService();
    }

    public function getLocalizedBioAttribute(): string
    {
        return app()->getLocale() === 'ar' ? ($this->bio_ar ?: $this->bio) : $this->bio;
    }

    /**
     * A coarse, display-only availability signal for discovery. Derived from the
     * weekly schedule and, when the caller knows it, whether the professional is
     * mid-job. One of: 'unavailable' | 'available_today' | 'available' | 'busy'.
     * Never a promise — just a hint for the customer.
     */
    public function availabilityStatus(?bool $busy = null): string
    {
        $rows = $this->relationLoaded('availability') ? $this->availability : $this->availability()->get();
        $open = collect($rows)->where('is_available', true);

        if ($open->isEmpty()) {
            return 'unavailable';
        }

        if ($busy === true) {
            return 'busy';
        }

        $now = now();
        $today = $now->dayOfWeekIso - 1;   // Carbon ISO Mon=1..Sun=7 -> 0..6 (schema order)
        $slot = $open->firstWhere('day_of_week', $today);

        if ($slot && $slot->start_time !== null && $slot->end_time !== null) {
            $hms = $now->format('H:i:s');
            if ($hms >= $slot->start_time && $hms <= $slot->end_time) {
                return 'available_today';
            }
        }

        return 'available';
    }
}
