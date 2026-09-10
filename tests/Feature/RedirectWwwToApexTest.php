<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Real production bug: every generated storage/asset URL is absolute and
 * always points at the non-www apex host (config('app.url')). A visitor
 * who lands on www.albatit.com still gets that same non-www HTML, so
 * every image becomes a cross-origin request under the CSP's
 * `img-src 'self'` and the browser silently blocks it — the page itself
 * loads fine, only the images fail, identically on every device/network
 * since it's a browser security rule, not a connectivity issue.
 */
class RedirectWwwToApexTest extends TestCase
{
    public function test_www_host_redirects_to_the_apex_domain_preserving_path_and_query(): void
    {
        Route::get('/probe-www-redirect', fn () => 'ok')->middleware('web');

        $response = $this->get('http://www.albatit.com/probe-www-redirect?foo=bar');

        $response->assertStatus(301);
        $response->assertRedirect('http://albatit.com/probe-www-redirect?foo=bar');
    }

    public function test_apex_host_is_not_redirected(): void
    {
        Route::get('/probe-apex-no-redirect', fn () => 'ok')->middleware('web');

        $response = $this->get('http://albatit.com/probe-apex-no-redirect');

        $response->assertOk();
        $response->assertSee('ok');
    }
}
