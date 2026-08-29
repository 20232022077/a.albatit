<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WallPost extends Model
{
    protected $primaryKey = 'content_item_id';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = ['content_item_id'];

    public function contentItem()
    {
        return $this->belongsTo(ContentItem::class);
    }
}
