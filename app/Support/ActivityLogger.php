<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Single write path for the admin activity log (see command 28's checklist:
 * user, operation, section, record id, date/time, IP, user agent, and
 * non-sensitive basic data). Every admin controller that mutates something
 * calls log() instead of inserting into activity_logs directly, so the
 * shape — and what never gets written to it — stays consistent in one place.
 */
class ActivityLogger
{
    /**
     * Attribute names that must never be written to the log's properties,
     * even if a caller accidentally passes a full model attribute array.
     */
    private const REDACTED_KEYS = ['password', 'password_confirmation', 'remember_token', 'token', 'secret', 'api_key'];

    public static function log(string $event, ?Model $subject = null, array $properties = []): void
    {
        $properties = array_diff_key($properties, array_flip(self::REDACTED_KEYS));

        DB::table('activity_logs')->insert([
            'user_id' => Auth::id(),
            'event' => $event,
            'subject_type' => $subject ? $subject->getMorphClass() : null,
            'subject_id' => $subject?->getKey(),
            'ip_address' => request()->ip(),
            'user_agent' => Str::limit((string) request()->userAgent(), 1000, ''),
            'properties' => $properties !== [] ? json_encode($properties, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) : null,
            'created_at' => now(),
        ]);
    }
}
