<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    protected $primaryKey = 'content_item_id';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = ['content_item_id', 'presenter', 'started_on', 'ended_on'];

    protected function casts(): array
    {
        return ['started_on' => 'date', 'ended_on' => 'date'];
    }

    public function contentItem()
    {
        return $this->belongsTo(ContentItem::class);
    }

    public function episodes()
    {
        return $this->hasMany(ProgramEpisode::class, 'program_id', 'content_item_id');
    }
}
