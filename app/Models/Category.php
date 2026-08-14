<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Category extends Model
{
    use SoftDeletes;

    private const CACHE_KEY = 'categories.active';

    protected $fillable = ['parent_id', 'name', 'slug', 'description', 'image_media_id', 'sort_order', 'is_active', 'meta'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer', 'meta' => 'array'];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Active categories rarely change, but every public listing page's
     * filter dropdown and the homepage both query them on every request.
     * Cache the full active set once and let callers filter/sort the
     * (small) in-memory collection however they need.
     */
    public static function cachedActive(): Collection
    {
        return Cache::remember(self::CACHE_KEY, now()->addHours(6), fn () => static::active()->orderBy('sort_order')->orderBy('name')->get());
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function image()
    {
        return $this->belongsTo(Media::class, 'image_media_id');
    }
}
