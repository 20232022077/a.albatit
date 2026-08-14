<?php

namespace Tests\Unit;

use App\Support\SafeYoutube;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SafeYoutubeTest extends TestCase
{
    #[DataProvider('validUrls')]
    public function test_extracts_video_id_from_valid_urls(string $url, string $expectedId): void
    {
        $this->assertSame($expectedId, SafeYoutube::extractVideoId($url));
    }

    public static function validUrls(): array
    {
        return [
            'watch url' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
            'short url' => ['https://youtu.be/dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
            'embed url' => ['https://www.youtube.com/embed/dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
            'shorts url' => ['https://youtube.com/shorts/dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
            'live url' => ['https://www.youtube.com/live/dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
            'nocookie host' => ['https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
            'watch with extra query params' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=30s', 'dQw4w9WgXcQ'],
        ];
    }

    #[DataProvider('maliciousOrInvalidUrls')]
    public function test_rejects_malicious_or_invalid_urls(?string $url): void
    {
        $this->assertNull(SafeYoutube::extractVideoId($url));
    }

    public static function maliciousOrInvalidUrls(): array
    {
        return [
            'javascript scheme' => ['javascript:alert(1)'],
            'data scheme' => ['data:text/html,<script>alert(1)</script>'],
            'look-alike host' => ['https://youtube.com.evil.com/watch?v=dQw4w9WgXcQ'],
            'unrelated host' => ['https://evil.com/watch?v=dQw4w9WgXcQ'],
            'not a youtube path' => ['https://www.youtube.com/'],
            'empty string' => [''],
            'null' => [null],
            'plain text' => ['not a url at all'],
            'wrong length id' => ['https://youtu.be/short'],
            'html injection attempt' => ['https://www.youtube.com/watch?v=<script>alert(1)</script>'],
        ];
    }

    public function test_embed_url_is_always_the_privacy_enhanced_nocookie_domain(): void
    {
        $embed = SafeYoutube::embedUrl('dQw4w9WgXcQ');

        $this->assertNotNull($embed);
        $this->assertStringStartsWith('https://www.youtube-nocookie.com/embed/', $embed);
    }

    public function test_embed_url_rejects_a_malformed_video_id(): void
    {
        $this->assertNull(SafeYoutube::embedUrl('<script>alert(1)</script>'));
        $this->assertNull(SafeYoutube::embedUrl('short'));
    }
}
