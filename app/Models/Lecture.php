<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lecture extends Model
{
    protected $primaryKey = 'content_item_id';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = ['content_item_id', 'speaker', 'delivered_at', 'venue'];

    protected function casts(): array
    {
        return ['delivered_at' => 'datetime'];
    }

    public function contentItem()
    {
        return $this->belongsTo(ContentItem::class);
    }
}
