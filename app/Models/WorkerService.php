<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkerService extends Model
{
    use HasFactory;

    protected $fillable = ['worker_id', 'service_category_id', 'price_from', 'is_active'];

    protected function casts(): array
    {
        return ['price_from' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }
}
