<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lecture extends Model
{
    protected $primaryKey = 'content_item_id';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = ['content_item_id', 'speaker', 'delivered_at', 'venue', 'audio_media_id', 'video_media_id'];

    protected function casts(): array
    {
        return ['delivered_at' => 'datetime'];
    }

    public function contentItem()
    {
        return $this->belongsTo(ContentItem::class);
    }

    public function audio()
    {
        return $this->belongsTo(Media::class, 'audio_media_id');
    }

    public function video()
    {
        return $this->belongsTo(Media::class, 'video_media_id');
    }
}
