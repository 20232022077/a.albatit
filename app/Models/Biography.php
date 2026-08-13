<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Biography extends Model
{
    protected $primaryKey = 'content_item_id';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = ['content_item_id', 'born_on', 'died_on', 'birthplace', 'profile_media_id'];

    public function contentItem()
    {
        return $this->belongsTo(ContentItem::class);
    }

    public function sections()
    {
        return $this->hasMany(BiographySection::class, 'biography_id', 'content_item_id')->orderBy('sort_order');
    }

    public function profileImage()
    {
        return $this->belongsTo(Media::class, 'profile_media_id');
    }
}
