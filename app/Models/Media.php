<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uploaded_by', 'disk', 'path', 'original_name', 'mime_type', 'size', 'checksum', 'width', 'height', 'metadata',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
