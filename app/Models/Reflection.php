<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reflection extends Model
{
    protected $primaryKey = 'content_item_id';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = ['content_item_id', 'surah_number', 'ayah_from', 'ayah_to'];

    public function contentItem()
    {
        return $this->belongsTo(ContentItem::class);
    }
}
