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
            // 'blob:' is needed for the admin book form's client-side PDF
            // cover preview (URL.createObjectURL on the canvas-rendered
            // page) — without it the browser silently blocks that <img>.
            "img-src 'self' data: blob:",
            "font-src 'self' https://fonts.bunny.net",
            // Without the bunny.net origin here, the <link rel="stylesheet">
            // that declares the @font-face rules is blocked by the browser
            // (font-src alone only covers the font files themselves), so
            // the Tajawal webfont silently fails to load site-wide. No
            // 'unsafe-inline': every decorative background that used to be
            // an inline style="" attribute is now a named utility class in
            // resources/css/app.css (bg-dot-grid-*), and nothing else in
            // the app emits inline styles or <style> blocks.
            "style-src 'self' https://fonts.bunny.net",
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
     * there — so script-src can stay strict with no 'unsafe-inline'.
     * 'wasm-unsafe-eval' only permits WebAssembly.instantiate (used by the
     * admin book form's client-side PDF-to-cover-image rendering); it does
     * not allow eval() or Function() of JS strings. Local development
     * additionally allows the Vite dev server for HMR when `npm run dev`
     * is used instead of a production build.
     */
    private function scriptSources(): string
    {
        return app()->environment('local')
            ? "'self' 'wasm-unsafe-eval' http://localhost:5173 http://127.0.0.1:5173"
            : "'self' 'wasm-unsafe-eval'";
    }

    private function connectSources(): string
    {
        return app()->environment('local')
            ? "'self' http://localhost:5173 http://127.0.0.1:5173 ws://localhost:5173 ws://127.0.0.1:5173"
            : "'self'";
    }
}
