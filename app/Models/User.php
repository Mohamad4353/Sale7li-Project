<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements HasLocalePreference, MustVerifyEmail
{
    use HasFactory, Notifiable;

    /** Send verification / notification mail in the account's own language. */
    public function preferredLocale(): string
    {
        return $this->locale ?: config('app.locale');
    }

    protected $fillable = ['name', 'email', 'phone', 'password', 'role', 'status', 'locale'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed'];
    }

    public function workerProfile(): HasOne
    {
        return $this->hasOne(WorkerProfile::class);
    }

    public function workerServices(): HasMany
    {
        return $this->hasMany(WorkerService::class, 'worker_id');
    }

    public function portfolioItems(): HasMany
    {
        return $this->hasMany(WorkerPortfolioItem::class, 'worker_id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function customerRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class, 'customer_id');
    }

    public function workerRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class, 'worker_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function reviewsWritten(): HasMany
    {
        return $this->hasMany(Review::class, 'customer_id');
    }

    public function reviewsReceived(): HasMany
    {
        return $this->hasMany(Review::class, 'worker_id');
    }

    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class, 'reporter_id');
    }

    public function favoriteWorkers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites', 'customer_id', 'worker_id');
    }

    public function isRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Active professionals with a verified profile AND at least one active
     * service — the only workers that appear in public marketplace discovery.
     * A verified worker who offers nothing is not yet ready to be found.
     */
    public function scopeVerifiedProfessional(Builder $query): Builder
    {
        return $query->where('role', 'worker')
            ->where('status', 'active')
            ->whereHas('workerProfile', fn (Builder $q) => $q->where('verification_status', 'verified'))
            ->whereHas('workerServices', fn (Builder $q) => $q->where('is_active', true));
    }
}
