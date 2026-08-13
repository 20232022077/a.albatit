<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContentItem extends Model
{
    use SoftDeletes;

    protected $fillable = ['author_id', 'type', 'title', 'slug', 'excerpt', 'body', 'status', 'is_featured', 'sort_order', 'published_at', 'meta'];

    protected function casts(): array
    {
        return ['is_featured' => 'boolean', 'sort_order' => 'integer', 'published_at' => 'datetime', 'meta' => 'array'];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeInCategory(Builder $query, string $categorySlug): Builder
    {
        return $query->whereHas('categories', fn (Builder $q) => $q->where('slug', $categorySlug));
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when(filled($term), fn (Builder $q) => $q->where(
            fn (Builder $inner) => $inner->where('title', 'like', "%{$term}%")
                ->orWhere('excerpt', 'like', "%{$term}%")
                ->orWhere('body', 'like', "%{$term}%")
        ));
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'content_category');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'content_tag');
    }

    public function media()
    {
        return $this->belongsToMany(Media::class, 'content_media')->withPivot('collection', 'alt_text', 'sort_order')->withTimestamps();
    }

    public function coverImage(): ?Media
    {
        return $this->media->firstWhere('pivot.collection', 'cover');
    }

    public function attachment(): ?Media
    {
        return $this->media->firstWhere('pivot.collection', 'attachment');
    }
}
