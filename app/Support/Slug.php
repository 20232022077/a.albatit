<?php

namespace App\Support;

use Illuminate\Support\Str;

class Slug
{
    /**
     * Normalizes arbitrary admin input (a title, or an explicit slug field)
     * into a URL-safe slug: whitespace/underscores become hyphens, anything
     * that isn't a letter/digit/hyphen is stripped, and leading/trailing
     * hyphens are trimmed. Falls back to a UUID if that leaves nothing
     * (e.g. a title written only in punctuation/emoji).
     */
    public static function sanitize(string $value): string
    {
        $value = preg_replace('/[\s_]+/u', '-', trim($value));
        $value = preg_replace('/[^\p{L}\p{N}\-]+/u', '', $value);
        $value = trim($value, '-');

        return $value !== '' ? $value : (string) Str::uuid();
    }
}
