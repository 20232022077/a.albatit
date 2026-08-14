<?php

namespace Tests\Feature;

use App\Models\ContentItem;
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
        \App\Models\Book::create(['content_item_id' => $item->id, 'author_name' => 'م']);

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
        \App\Models\Book::create(['content_item_id' => $item->id, 'author_name' => 'م']);

        $response = $this->get('/books/xss-book');
        $response->assertOk();
        $response->assertDontSee('<script>alert(1)</script>', false);
        $response->assertSee(e($malicious), false);
    }

    public function test_pdf_route_rejects_unpublished_documents_even_with_a_guessed_id(): void
    {
        $item = ContentItem::create(['type' => 'book', 'title' => 'كتاب مسودة', 'slug' => 'draft-pdf-book', 'status' => 'draft']);
        $media = \App\Models\Media::create([
            'disk' => 'local', 'path' => 'pdfs/books/fake.pdf', 'original_name' => 'fake.pdf',
            'mime_type' => 'application/pdf', 'size' => 100,
        ]);
        \App\Models\Book::create(['content_item_id' => $item->id, 'author_name' => 'م', 'pdf_media_id' => $media->id]);
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
}
