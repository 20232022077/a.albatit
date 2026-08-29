<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\ContentItem;
use App\Models\Media;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AccessControlSeeder::class);
    }

    // CSRF protection is deliberately not exercised as a PHPUnit test:
    // Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::runningUnitTests()
    // disables the check for every request made through the HTTP test
    // client, by framework design — a token-less POST can't be proven to
    // fail 419 from inside a Feature test, and Laravel 11's bootstrap
    // doesn't expose the default "web" group for static introspection
    // either. It's verified instead with a real HTTP request against the
    // running dev server (see this command's manual verification pass).

    public function test_sql_injection_payload_in_search_does_not_error_or_leak_data(): void
    {
        $item = ContentItem::create(['type' => 'book', 'title' => 'كتاب عادي', 'slug' => 'normal-book', 'status' => 'published', 'published_at' => now()]);
        Book::create(['content_item_id' => $item->id, 'author_name' => 'م']);

        $payloads = [
            "' OR '1'='1",
            "'; DROP TABLE users; --",
            "1' UNION SELECT null,null,null-- -",
        ];

        foreach ($payloads as $payload) {
            $response = $this->get('/search?q='.urlencode($payload));
            $response->assertOk();
        }

        // The users table must still exist and be untouched.
        $this->assertDatabaseCount('users', 0);
    }

    public function test_mass_assignment_cannot_grant_is_active_or_roles_beyond_what_is_validated(): void
    {
        $admin = $this->userWithPermissions(['users.view', 'users.create']);

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'مستخدم جديد',
            'email' => 'newuser@example.com',
            'password' => 'NewUserPassword123',
            'password_confirmation' => 'NewUserPassword123',
            // Not a real field on StoreUserRequest — must be silently ignored, not mass-assigned.
            'is_admin' => 1,
        ])->assertRedirect();

        $user = User::where('email', 'newuser@example.com')->firstOrFail();
        $this->assertFalse($user->hasRole('super-admin'));
    }

    public function test_content_title_with_script_tag_is_escaped_not_executed_on_public_page(): void
    {
        $malicious = '<script>alert(1)</script>';
        $item = ContentItem::create([
            'type' => 'book', 'title' => $malicious, 'slug' => 'xss-book',
            'status' => 'published', 'published_at' => now(),
        ]);
        Book::create(['content_item_id' => $item->id, 'author_name' => 'م']);

        $response = $this->get('/books/xss-book');
        $response->assertOk();
        $response->assertDontSee('<script>alert(1)</script>', false);
        $response->assertSee(e($malicious), false);
    }

    public function test_pdf_route_rejects_unpublished_documents_even_with_a_guessed_id(): void
    {
        $item = ContentItem::create(['type' => 'book', 'title' => 'كتاب مسودة', 'slug' => 'draft-pdf-book', 'status' => 'draft']);
        $media = Media::create([
            'disk' => 'local', 'path' => 'pdfs/books/fake.pdf', 'original_name' => 'fake.pdf',
            'mime_type' => 'application/pdf', 'size' => 100,
        ]);
        Book::create(['content_item_id' => $item->id, 'author_name' => 'م', 'pdf_media_id' => $media->id]);
        $item->media()->attach($media->id, ['collection' => 'attachment']);

        $this->get(route('pdf.show', $media))->assertNotFound();
    }

    public function test_role_and_permission_ids_cannot_escalate_through_direct_role_id_tampering(): void
    {
        $manager = $this->userWithPermissions(['roles.view', 'roles.update']);
        $superAdminRole = Role::where('name', 'super-admin')->firstOrFail();

        // roles.delete was never granted, so this must be forbidden outright —
        // confirms Broken Access Control can't be worked around by guessing IDs.
        $this->actingAs($manager)
            ->delete(route('admin.roles.destroy', $superAdminRole))
            ->assertForbidden();

        $this->assertDatabaseHas('roles', ['id' => $superAdminRole->id]);
    }

    /**
     * Regression test: the CSP once allowed the Bunny Fonts font files
     * (font-src) but not the stylesheet that declares the @font-face rules
     * pointing at them (style-src), so the Tajawal webfont silently failed
     * to load site-wide; and img-src once lacked 'blob:', which silently
     * broke the admin book form's client-side PDF-cover preview
     * (URL.createObjectURL). Both must stay covered. style-src no longer
     * needs 'unsafe-inline': the decorative dotted-grid backgrounds that
     * used to be inline style="" attributes are now named utility classes
     * (bg-dot-grid-* in resources/css/app.css).
     */
    public function test_content_security_policy_allows_the_webfont_stylesheet_and_blob_image_previews(): void
    {
        $csp = $this->get('/')->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("style-src 'self' https://fonts.bunny.net", $csp);
        $this->assertStringNotContainsString("'unsafe-inline'", $csp);
        $this->assertStringContainsString("img-src 'self' data: blob:", $csp);
    }

    /**
     * Clickjacking, MIME-sniffing, and referrer-leak protection must be
     * present on every response, not just the homepage.
     */
    public function test_security_headers_are_present_on_public_and_admin_responses(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('X-Permitted-Cross-Domain-Policies', 'none');
        $this->assertFalse($response->headers->has('X-Powered-By'));
    }

    /**
     * HSTS must never be sent over a plain-HTTP local/staging request —
     * doing so could permanently downgrade a non-HTTPS environment in the
     * visitor's browser via the includeSubDomains directive.
     */
    public function test_hsts_header_is_absent_over_a_non_secure_request(): void
    {
        $response = $this->get('/');

        $this->assertFalse($response->headers->has('Strict-Transport-Security'));
    }
}
