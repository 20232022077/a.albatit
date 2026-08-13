<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    protected $primaryKey = 'content_item_id';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'content_item_id', 'author_name', 'isbn', 'publisher', 'publication_year', 'pages_count', 'cover_media_id', 'pdf_media_id',
    ];

    public function contentItem()
    {
        return $this->belongsTo(ContentItem::class);
    }

    public function cover()
    {
        return $this->belongsTo(Media::class, 'cover_media_id');
    }

    public function pdf()
    {
        return $this->belongsTo(Media::class, 'pdf_media_id');
    }
}
