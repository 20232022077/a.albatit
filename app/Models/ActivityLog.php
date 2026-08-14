<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'event', 'subject_type', 'subject_id', 'ip_address', 'user_agent', 'properties'];

    protected function casts(): array
    {
        return ['properties' => 'array', 'created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The part of the dot-notation event name before the first dot, e.g.
     * "books" from "books.created" — used as the "القسم" (section) column
     * in the admin log viewer without needing a redundant stored column.
     */
    public function section(): string
    {
        return explode('.', $this->event)[0];
    }
}
