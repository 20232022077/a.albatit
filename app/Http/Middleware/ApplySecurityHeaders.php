<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplySecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // Clickjacking: redundant with the CSP frame-ancestors directive,
        // kept for browsers that only honor the older header.
        $response->headers->set('X-Frame-Options', 'DENY');

        // Stops the browser from guessing (“sniffing”) a file’s type from
        // its content, e.g. treating an uploaded image as executable HTML.
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Never leak the full URL (which can contain query strings) to
        // other origins; same-origin navigation keeps the full referrer.
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');

        if ($request->isSecure() && app()->environment('production')) {
            // 6 months, applies to subdomains; only sent over an actual
            // HTTPS connection so it can never downgrade a plain-HTTP
            // local/staging environment.
            $response->headers->set('Strict-Transport-Security', 'max-age=15552000; includeSubDomains');
        }

        // The PHP version is server fingerprinting information visitors
        // don't need; header_remove() strips it regardless of how the SAPI
        // queued it (belt-and-suspenders alongside expose_php=Off in php.ini).
        if (! app()->runningInConsole()) {
            header_remove('X-Powered-By');
        }

        return $response;
    }
}
