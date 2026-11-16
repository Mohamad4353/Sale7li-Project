<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A tiny key/value store for platform-wide settings. Today it holds exactly one
 * key — `commission_rate` — which is the single source of truth for the active
 * marketplace commission rate (a 4-decimal fraction, e.g. "0.1000" = 10%).
 *
 * The row is seeded by the financial-foundation migration. Reads go through
 * CommissionService; there is no config-file fallback rate.
 */
class PlatformSetting extends Model
{
    public const COMMISSION_RATE = 'commission_rate';

    protected $fillable = ['key', 'value'];

    public static function getValue(string $key, ?string $default = null): ?string
    {
        $value = static::query()->where('key', $key)->value('value');

        return $value ?? $default;
    }

    public static function setValue(string $key, string $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
