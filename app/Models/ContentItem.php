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
        return ['is_featured' => 'boolean', 'sort_order' => 'integer', 'likes_count' => 'integer', 'published_at' => 'datetime', 'meta' => 'array'];
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
        $words = collect(preg_split('/\s+/u', trim((string) $term), -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn (string $word) => preg_replace('/[+\-<>()~*"@]+/u', '', $word))
            ->filter(fn (string $word) => $word !== '');

        if ($words->isEmpty()) {
            return $query;
        }

        $boolean = $words->map(fn (string $word) => "+{$word}*")->implode(' ');

        return $query->whereRaw('MATCH(title, excerpt, body) AGAINST (? IN BOOLEAN MODE)', [$boolean]);
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

    public function book()
    {
        return $this->hasOne(Book::class, 'content_item_id');
    }

    public function biography()
    {
        return $this->hasOne(Biography::class, 'content_item_id');
    }

    public function program()
    {
        return $this->hasOne(Program::class, 'content_item_id');
    }

    public function programEpisode()
    {
        return $this->hasOne(ProgramEpisode::class, 'content_item_id');
    }

    public function lecture()
    {
        return $this->hasOne(Lecture::class, 'content_item_id');
    }

    public function reflection()
    {
        return $this->hasOne(Reflection::class, 'content_item_id');
    }

    public function wallPost()
    {
        return $this->hasOne(WallPost::class, 'content_item_id');
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
