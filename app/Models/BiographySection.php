<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BiographySection extends Model
{
    protected $fillable = ['type', 'title', 'body', 'sort_order', 'is_visible'];

    public function biography()
    {
        return $this->belongsTo(Biography::class, 'biography_id', 'content_item_id');
    }
}
