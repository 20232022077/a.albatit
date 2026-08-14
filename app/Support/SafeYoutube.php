<?php

namespace App\Support;

class SafeYoutube
{
    /**
     * Only these hosts are ever accepted as input. Anything else
     * (including look-alike domains) is rejected outright.
     */
    private const ALLOWED_HOSTS = [
        'youtube.com',
        'www.youtube.com',
        'm.youtube.com',
        'youtube-nocookie.com',
        'www.youtube-nocookie.com',
        'youtu.be',
    ];

    /**
     * YouTube video IDs are always exactly 11 characters from this set.
     */
    private const VIDEO_ID_PATTERN = '/^[A-Za-z0-9_-]{11}$/';

    /**
     * Validate a URL and extract its YouTube video ID, or null if the URL
     * is not a recognizable, safe link to a single YouTube video. Never
     * trusts the URL beyond parsing it: no scheme other than http/https is
     * accepted (blocking javascript:, data:, etc.), and the host must be an
     * exact match against the allow-list above.
     */
    public static function extractVideoId(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }

        $parts = parse_url($url);
        if ($parts === false || empty($parts['host'])) {
            return null;
        }

        if (! in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)) {
            return null;
        }

        $host = strtolower($parts['host']);
        if (! in_array($host, self::ALLOWED_HOSTS, true)) {
            return null;
        }

        $path = $parts['path'] ?? '';
        parse_str($parts['query'] ?? '', $query);

        $id = match (true) {
            $host === 'youtu.be' => ltrim($path, '/'),
            str_starts_with($path, '/watch') => $query['v'] ?? null,
            str_starts_with($path, '/embed/') => substr($path, 7),
            str_starts_with($path, '/shorts/') => substr($path, 8),
            str_starts_with($path, '/live/') => substr($path, 6),
            str_starts_with($path, '/v/') => substr($path, 3),
            default => null,
        };

        if (! $id) {
            return null;
        }

        $id = explode('/', $id)[0];
        $id = explode('?', $id)[0];
        $id = explode('&', $id)[0];

        return preg_match(self::VIDEO_ID_PATTERN, $id) === 1 ? $id : null;
    }

    public static function isValid(?string $url): bool
    {
        return self::extractVideoId($url) !== null;
    }

    /**
     * Build a safe, privacy-enhanced embed URL from a validated video ID.
     * Never accepts a raw URL or HTML from the caller — this is the only
     * way an <iframe src> should ever be produced for YouTube content.
     */
    public static function embedUrl(string $videoId): ?string
    {
        if (preg_match(self::VIDEO_ID_PATTERN, $videoId) !== 1) {
            return null;
        }

        return 'https://www.youtube-nocookie.com/embed/'.$videoId.'?rel=0&modestbranding=1';
    }
}
