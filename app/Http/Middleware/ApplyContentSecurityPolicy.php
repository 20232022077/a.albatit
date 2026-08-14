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

        $directives = [
            "default-src 'self'",
            'frame-src '.self::FRAME_SOURCES,
            'child-src '.self::FRAME_SOURCES,
            // Nothing on the site embeds it in a frame; blocks clickjacking
            // alongside the X-Frame-Options header below.
            "frame-ancestors 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "img-src 'self' data:",
            "font-src 'self' https://fonts.bunny.net",
            "style-src 'self' 'unsafe-inline'",
            'script-src '.$this->scriptSources(),
            'connect-src '.$this->connectSources(),
        ];

        $response->headers->set('Content-Security-Policy', implode('; ', $directives));

        return $response;
    }

    /**
     * All application JavaScript ships as a same-origin Vite build
     * (resources/js/app.js, compiled to public/build/...), and every inline
     * onclick/onchange handler has been moved to data-attributes handled
     * there — so script-src can stay strict with no 'unsafe-inline'. Local
     * development additionally allows the Vite dev server for HMR when
     * `npm run dev` is used instead of a production build.
     */
    private function scriptSources(): string
    {
        return app()->environment('local')
            ? "'self' http://localhost:5173 http://127.0.0.1:5173"
            : "'self'";
    }

    private function connectSources(): string
    {
        return app()->environment('local')
            ? "'self' http://localhost:5173 http://127.0.0.1:5173 ws://localhost:5173 ws://127.0.0.1:5173"
            : "'self'";
    }
}
