<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;

/**
 * Generic key-value store for site-wide settings. Adding a new setting
 * never needs a migration: pick a "group.key" name and read/write it
 * through get()/set(). Values are always stored as plain strings; callers
 * that need JSON (e.g. a list of social links) encode/decode it themselves.
 */
class Setting extends Model
{
    private const CACHE_KEY = 'settings.bag';

    protected $fillable = ['key', 'value'];

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::bag()[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        if ($value === null || $value === '') {
            static::where('key', $key)->delete();
        } else {
            static::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        self::flush();
    }

    /**
     * Read on every request via SiteSettings (shared into every view), so
     * this must survive being called before the settings table exists yet
     * — a fresh install running its first `migrate` boots the app (and
     * therefore AppServiceProvider) before that migration has run.
     */
    public static function bag(): array
    {
        try {
            return Cache::remember(self::CACHE_KEY, now()->addHours(6), function () {
                return static::query()->pluck('value', 'key')->all();
            });
        } catch (QueryException) {
            return [];
        }
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
