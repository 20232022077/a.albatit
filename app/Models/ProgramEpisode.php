<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgramEpisode extends Model
{
    protected $primaryKey = 'content_item_id';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = ['content_item_id', 'program_id', 'episode_number', 'aired_at'];

    protected function casts(): array
    {
        return ['aired_at' => 'datetime'];
    }

    public function contentItem()
    {
        return $this->belongsTo(ContentItem::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class, 'program_id', 'content_item_id');
    }
}
