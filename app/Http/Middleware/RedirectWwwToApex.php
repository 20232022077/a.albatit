<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectWwwToApex
{
    /**
     * Every generated asset/storage URL is absolute and always points at
     * the non-www host (config('app.url')). A visitor who lands on the
     * www host still gets that same non-www HTML, so every image becomes
     * a cross-origin request under the CSP's `img-src 'self'` and the
     * browser silently blocks it — the page itself loads, only the
     * images fail. Redirecting www to the apex domain up front keeps the
     * page's own origin and its asset URLs identical, everywhere.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();

        if (str_starts_with($host, 'www.')) {
            $apexHost = substr($host, 4);
            $apexUrl = $request->getScheme().'://'.$apexHost.$request->getRequestUri();

            return redirect()->to($apexUrl, 301);
        }

        return $next($request);
    }
}
