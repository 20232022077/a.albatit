<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ContentItem;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class ContentCrudTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AccessControlSeeder::class);
        Storage::fake('public');
        Storage::fake('local');
    }

    private function fakePdf(string $name = 'book.pdf'): UploadedFile
    {
        // A real minimal PDF, not just random bytes: SafeFileUpload checks
        // for the %PDF- magic header on the actual file content.
        return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n%%EOF");
    }

    public function test_admin_can_create_a_book_with_cover_and_pdf(): void
    {
        $admin = $this->userWithPermissions(['content.view', 'content.create']);

        $response = $this->actingAs($admin)->post(route('admin.books.store'), [
            'title' => 'كتاب اختبار',
            'author_name' => 'مؤلف اختبار',
            'status' => 'published',
            'cover_image' => UploadedFile::fake()->image('cover.jpg', 400, 600),
            'pdf_file' => $this->fakePdf(),
        ]);

        $response->assertRedirect(route('admin.books.index'));
        $this->assertDatabaseHas('content_items', ['title' => 'كتاب اختبار', 'type' => 'book', 'status' => 'published']);
    }

    public function test_creating_a_book_without_required_files_fails_validation(): void
    {
        $admin = $this->userWithPermissions(['content.view', 'content.create']);

        $this->actingAs($admin)->post(route('admin.books.store'), [
            'title' => 'كتاب بدون ملفات',
            'author_name' => 'مؤلف',
            'status' => 'published',
        ])->assertSessionHasErrors(['cover_image', 'pdf_file']);

        $this->assertDatabaseMissing('content_items', ['title' => 'كتاب بدون ملفات']);
    }

    public function test_a_malicious_file_disguised_as_a_pdf_is_rejected(): void
    {
        $admin = $this->userWithPermissions(['content.view', 'content.create']);
        $fakePdf = UploadedFile::fake()->createWithContent('evil.pdf', "%PDF-1.4\n/OpenAction << /S /Launch /Win << /F (cmd.exe) >> >>\n%%EOF");

        $this->actingAs($admin)->post(route('admin.books.store'), [
            'title' => 'كتاب خبيث',
            'author_name' => 'مؤلف',
            'status' => 'published',
            'cover_image' => UploadedFile::fake()->image('cover.jpg'),
            'pdf_file' => $fakePdf,
        ])->assertSessionHasErrors('file');

        $this->assertDatabaseMissing('content_items', ['title' => 'كتاب خبيث']);
    }

    public function test_admin_can_update_delete_restore_publish_and_unpublish_a_book(): void
    {
        $admin = $this->userWithPermissions(['content.view', 'content.create', 'content.update', 'content.delete']);

        $item = ContentItem::create(['type' => 'book', 'title' => 'كتاب أصلي', 'slug' => 'original-book', 'status' => 'draft', 'author_id' => $admin->id]);
        \App\Models\Book::create(['content_item_id' => $item->id, 'author_name' => 'مؤلف']);

        $this->actingAs($admin)->post(route('admin.books.publish', $item))->assertRedirect();
        $this->assertSame('published', $item->fresh()->status);

        $this->actingAs($admin)->post(route('admin.books.unpublish', $item))->assertRedirect();
        $this->assertSame('unpublished', $item->fresh()->status);

        $this->actingAs($admin)->delete(route('admin.books.destroy', $item))->assertRedirect();
        $this->assertSoftDeleted('content_items', ['id' => $item->id]);

        $this->actingAs($admin)->post(route('admin.books.restore', $item->id))->assertRedirect();
        $this->assertDatabaseHas('content_items', ['id' => $item->id, 'deleted_at' => null]);
    }

    public function test_soft_deleted_content_is_hidden_from_public_listing_and_show_page(): void
    {
        $item = ContentItem::create(['type' => 'book', 'title' => 'كتاب محذوف', 'slug' => 'deleted-book', 'status' => 'published', 'published_at' => now()]);
        \App\Models\Book::create(['content_item_id' => $item->id, 'author_name' => 'مؤلف']);
        $item->delete();

        $this->get('/books')->assertDontSee('كتاب محذوف');
        $this->get('/books/deleted-book')->assertNotFound();
    }

    public function test_unpublished_and_draft_content_returns_404_on_public_site(): void
    {
        $draft = ContentItem::create(['type' => 'book', 'title' => 'كتاب مسودة', 'slug' => 'draft-book', 'status' => 'draft']);
        \App\Models\Book::create(['content_item_id' => $draft->id, 'author_name' => 'مؤلف']);

        $unpublished = ContentItem::create(['type' => 'book', 'title' => 'كتاب غير منشور', 'slug' => 'unpublished-book', 'status' => 'unpublished', 'published_at' => now()->subDay()]);
        \App\Models\Book::create(['content_item_id' => $unpublished->id, 'author_name' => 'مؤلف']);

        $this->get('/books/draft-book')->assertNotFound();
        $this->get('/books/unpublished-book')->assertNotFound();
        $this->get('/books')->assertDontSee('كتاب مسودة')->assertDontSee('كتاب غير منشور');
    }

    public function test_published_content_is_visible_on_public_site(): void
    {
        $item = ContentItem::create(['type' => 'book', 'title' => 'كتاب منشور', 'slug' => 'published-book', 'status' => 'published', 'published_at' => now(), 'excerpt' => 'وصف الكتاب']);
        \App\Models\Book::create(['content_item_id' => $item->id, 'author_name' => 'مؤلف الكتاب']);

        $this->get('/books')->assertOk()->assertSee('كتاب منشور');
        $this->get('/books/published-book')->assertOk()->assertSee('كتاب منشور');
    }

    /**
     * IDOR guard: BookController's admin edit/update/destroy actions all
     * call authorizeBookItem(), which aborts with 404 unless the resolved
     * ContentItem's type is actually "book". Without this, /admin/books/{id}
     * would happily operate on a lecture/reflection/etc. content item purely
     * because the numeric id matched.
     */
    public function test_book_controller_rejects_a_content_item_of_a_different_type(): void
    {
        $admin = $this->userWithPermissions(['content.view', 'content.update', 'content.delete']);

        $lecture = ContentItem::create(['type' => 'lecture', 'title' => 'محاضرة', 'slug' => 'a-lecture', 'status' => 'draft']);
        \App\Models\Lecture::create(['content_item_id' => $lecture->id]);

        $this->actingAs($admin)->get(route('admin.books.edit', $lecture))->assertNotFound();
        $this->actingAs($admin)->delete(route('admin.books.destroy', $lecture))->assertNotFound();
    }

    public function test_category_filter_only_returns_matching_published_books(): void
    {
        $categoryA = Category::create(['name' => 'تصنيف أ', 'slug' => 'cat-a', 'is_active' => true]);
        $categoryB = Category::create(['name' => 'تصنيف ب', 'slug' => 'cat-b', 'is_active' => true]);

        $itemA = ContentItem::create(['type' => 'book', 'title' => 'كتاب أ', 'slug' => 'book-a', 'status' => 'published', 'published_at' => now()]);
        \App\Models\Book::create(['content_item_id' => $itemA->id, 'author_name' => 'م']);
        $itemA->categories()->attach($categoryA->id);

        $itemB = ContentItem::create(['type' => 'book', 'title' => 'كتاب ب', 'slug' => 'book-b', 'status' => 'published', 'published_at' => now()]);
        \App\Models\Book::create(['content_item_id' => $itemB->id, 'author_name' => 'م']);
        $itemB->categories()->attach($categoryB->id);

        $response = $this->get('/books?category_id='.$categoryA->id);
        $response->assertSee('كتاب أ')->assertDontSee('كتاب ب');
    }
}
