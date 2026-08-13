<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Biography extends Model { protected $primaryKey = 'content_item_id'; public $incrementing = false; protected $keyType = 'int'; protected $fillable = ['born_on','died_on','birthplace']; public function contentItem() { return $this->belongsTo(ContentItem::class); } public function sections() { return $this->hasMany(BiographySection::class, 'biography_id', 'content_item_id')->orderBy('sort_order'); } }
