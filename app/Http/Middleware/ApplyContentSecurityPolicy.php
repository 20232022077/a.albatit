<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyContentSecurityPolicy
{
    /**
     * Restrict where the page is allowed to embed frames from. This is the
     * concrete enforcement behind the YouTube embed system: even if a video
     * URL or the generated <iframe src> were ever manipulated, the browser
     * itself refuses to load a frame from anywhere outside this list.
     */
    private const FRAME_SOURCES = "'self' https://www.youtube-nocookie.com";

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Content-Security-Policy', implode('; ', [
            'frame-src '.self::FRAME_SOURCES,
            'child-src '.self::FRAME_SOURCES,
            "object-src 'none'",
            "base-uri 'self'",
        ]));

        return $response;
    }
}
