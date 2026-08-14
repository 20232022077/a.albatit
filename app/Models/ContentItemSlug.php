<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * An append-only log of a content item's retired slugs. When a slug no
 * longer resolves to a current content item, public controllers check
 * here to issue a permanent redirect to the item's current URL instead
 * of a dead 404.
 */
class ContentItemSlug extends Model
{
    public $timestamps = false;

    protected $fillable = ['content_item_id', 'slug'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function contentItem()
    {
        return $this->belongsTo(ContentItem::class);
    }
}
