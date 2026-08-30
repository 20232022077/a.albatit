<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use App\Models\ContentItem;
use App\Models\Lecture;
use App\Models\Program;
use App\Models\ProgramEpisode;
use App\Models\Reflection;
use App\Models\WallPost;
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

    /**
     * Regression test: a book's PDF is linked only through the dedicated
     * books.pdf_media_id column (see BookController/ManagesContentItems),
     * never through the generic content_media pivot. The public pdf.show
     * route must still recognize it as belonging to a published item and
     * serve it — it previously always 404'd for every book because its
     * authorization check only looked at the pivot.
     */
    public function test_a_freshly_created_published_books_pdf_is_publicly_reachable(): void
    {
        $admin = $this->userWithPermissions(['content.view', 'content.create']);

        $this->actingAs($admin)->post(route('admin.books.store'), [
            'title' => 'كتاب قابل للقراءة',
            'author_name' => 'مؤلف اختبار',
            'status' => 'published',
            'pdf_file' => $this->fakePdf(),
        ]);

        $book = Book::whereHas('contentItem', fn ($q) => $q->where('title', 'كتاب قابل للقراءة'))->firstOrFail();

        $this->get(route('pdf.show', $book->pdf_media_id))->assertOk();
    }

    public function test_creating_a_book_without_a_pdf_file_fails_validation(): void
    {
        $admin = $this->userWithPermissions(['content.view', 'content.create']);

        $this->actingAs($admin)->post(route('admin.books.store'), [
            'title' => 'كتاب بدون ملفات',
            'author_name' => 'مؤلف',
            'status' => 'published',
        ])->assertSessionHasErrors(['pdf_file']);

        $this->assertDatabaseMissing('content_items', ['title' => 'كتاب بدون ملفات']);
    }

    public function test_admin_can_create_a_book_with_a_pdf_but_no_cover_image(): void
    {
        $admin = $this->userWithPermissions(['content.view', 'content.create']);

        $response = $this->actingAs($admin)->post(route('admin.books.store'), [
            'title' => 'كتاب بدون غلاف',
            'author_name' => 'مؤلف اختبار',
            'status' => 'published',
            'pdf_file' => $this->fakePdf(),
        ]);

        $response->assertRedirect(route('admin.books.index'));
        $this->assertDatabaseHas('content_items', ['title' => 'كتاب بدون غلاف', 'type' => 'book', 'status' => 'published']);
    }

    /**
     * Regression test: quraniyat/reflections/lectures/programs all used to
     * require cover_image on create (only book/quran-centrality/wall-posts
     * didn't). Made consistent with the rest of the platform.
     */
    public function test_admin_can_create_a_quraniyat_item_without_a_cover_image(): void
    {
        $admin = $this->userWithPermissions(['content.view', 'content.create']);
        Category::create(['name' => 'قرآنيات', 'slug' => 'quraniyat']);

        $response = $this->actingAs($admin)->post(route('admin.quraniyat.store'), [
            'type' => 'article',
            'title' => 'مقال قرآنيات بدون غلاف',
            'body' => 'نص المقال',
            'status' => 'published',
        ]);

        $response->assertRedirect(route('admin.quraniyat.index'));
        $this->assertDatabaseHas('content_items', ['title' => 'مقال قرآنيات بدون غلاف', 'status' => 'published']);
    }

    public function test_admin_can_create_a_reflection_without_a_cover_image(): void
    {
        $admin = $this->userWithPermissions(['content.view', 'content.create']);

        $response = $this->actingAs($admin)->post(route('admin.reflections.store'), [
            'title' => 'تأمل بدون غلاف',
            'body' => 'نص التأمل',
            'status' => 'published',
        ]);

        $response->assertRedirect(route('admin.reflections.index'));
        $this->assertDatabaseHas('content_items', ['title' => 'تأمل بدون غلاف', 'status' => 'published']);
    }

    public function test_admin_can_create_a_lecture_without_a_cover_image(): void
    {
        $admin = $this->userWithPermissions(['content.view', 'content.create']);

        $response = $this->actingAs($admin)->post(route('admin.lectures.store'), [
            'title' => 'محاضرة بدون غلاف',
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'status' => 'published',
        ]);

        $response->assertRedirect(route('admin.lectures.index'));
        $this->assertDatabaseHas('content_items', ['title' => 'محاضرة بدون غلاف', 'status' => 'published']);
    }

    public function test_admin_can_create_a_program_without_a_cover_image(): void
    {
        $admin = $this->userWithPermissions(['content.view', 'content.create']);

        $response = $this->actingAs($admin)->post(route('admin.programs.store'), [
            'title' => 'برنامج بدون غلاف',
            'status' => 'published',
        ]);

        $response->assertRedirect(route('admin.programs.index'));
        $this->assertDatabaseHas('content_items', ['title' => 'برنامج بدون غلاف', 'status' => 'published']);
    }

    public function test_admin_can_remove_a_books_cover_image_without_uploading_a_replacement(): void
    {
        $admin = $this->userWithPermissions(['content.view', 'content.create', 'content.update']);

        $this->actingAs($admin)->post(route('admin.books.store'), [
            'title' => 'كتاب له غلاف',
            'author_name' => 'مؤلف اختبار',
            'status' => 'published',
            'cover_image' => UploadedFile::fake()->image('cover.jpg', 400, 600),
            'pdf_file' => $this->fakePdf(),
        ]);

        $item = ContentItem::where('title', 'كتاب له غلاف')->firstOrFail();
        $this->assertNotNull($item->book->cover_media_id);
        $oldCoverMediaId = $item->book->cover_media_id;

        $this->actingAs($admin)->put(route('admin.books.update', $item), [
            'title' => 'كتاب له غلاف',
            'author_name' => 'مؤلف اختبار',
            'status' => 'published',
            'remove_cover_image' => '1',
        ])->assertRedirect(route('admin.books.index'));

        $this->assertNull($item->book->fresh()->cover_media_id);
        $this->assertSoftDeleted('media', ['id' => $oldCoverMediaId]);
    }

    public function test_admin_can_remove_a_reflections_cover_image_without_uploading_a_replacement(): void
    {
        $admin = $this->userWithPermissions(['content.view', 'content.create', 'content.update']);

        $this->actingAs($admin)->post(route('admin.reflections.store'), [
            'title' => 'تأمل له غلاف',
            'body' => 'نص التأمل',
            'status' => 'published',
            'cover_image' => UploadedFile::fake()->image('cover.jpg'),
        ]);

        $item = ContentItem::where('title', 'تأمل له غلاف')->firstOrFail();
        $this->assertNotNull($item->coverImage());

        $this->actingAs($admin)->put(route('admin.reflections.update', $item), [
            'title' => 'تأمل له غلاف',
            'body' => 'نص التأمل',
            'status' => 'published',
            'remove_cover_image' => '1',
        ])->assertRedirect(route('admin.reflections.index'));

        $this->assertNull($item->fresh()->coverImage());
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
        Book::create(['content_item_id' => $item->id, 'author_name' => 'مؤلف']);

        $this->actingAs($admin)->post(route('admin.books.publish', $item))->assertRedirect();
        $this->assertSame('published', $item->fresh()->status);

        $this->actingAs($admin)->post(route('admin.books.unpublish', $item))->assertRedirect();
        $this->assertSame('unpublished', $item->fresh()->status);

        $this->actingAs($admin)->delete(route('admin.books.destroy', $item))->assertRedirect();
        $this->assertSoftDeleted('content_items', ['id' => $item->id]);

        $this->actingAs($admin)->post(route('admin.books.restore', $item->id))->assertRedirect();
        $this->assertDatabaseHas('content_items', ['id' => $item->id, 'deleted_at' => null]);
    }

    public function test_admin_can_update_a_program_without_changing_its_slug(): void
    {
        // Regression test: UpdateProgramRequest used to look up the route
        // parameter as "program" while the route actually binds it as
        // "item", so Rule::unique(...)->ignore() never excluded the item
        // being edited — any update that kept the same (unchanged) slug
        // was rejected as "slug already in use" against itself.
        $admin = $this->userWithPermissions(['content.view', 'content.create', 'content.update']);

        $item = ContentItem::create(['type' => 'program', 'title' => 'برنامج أصلي', 'slug' => 'original-program', 'status' => 'draft', 'author_id' => $admin->id]);
        Program::create(['content_item_id' => $item->id]);

        $response = $this->actingAs($admin)->put(route('admin.programs.update', $item), [
            'title' => 'برنامج أصلي',
            'slug' => 'original-program',
            'presenter' => 'الشيخ فلان',
            'status' => 'draft',
        ]);

        $response->assertRedirect(route('admin.programs.index'));
        $response->assertSessionHasNoErrors();
        $this->assertSame('الشيخ فلان', $item->program->fresh()->presenter);
    }

    /**
     * The Book and Program lifecycle tests above already proved this exact
     * flow once; the remaining six content types share the identical
     * ManagesContentItems-based controller code, so this is a systematic
     * sweep (not a guess) confirming none of them has a type-specific
     * divergence of the same kind the Program regression test caught.
     */
    public function test_admin_can_update_delete_restore_publish_and_unpublish_a_quraniyat_item(): void
    {
        $admin = $this->userWithPermissions(['content.view', 'content.create', 'content.update', 'content.delete']);
        $category = Category::create(['name' => 'قرآنيات', 'slug' => 'quraniyat']);

        $item = ContentItem::create(['type' => 'article', 'title' => 'مقال أصلي', 'slug' => 'original-quraniyat', 'status' => 'draft', 'author_id' => $admin->id]);
        $item->categories()->attach($category);

        $update = $this->actingAs($admin)->put(route('admin.quraniyat.update', $item), [
            'title' => 'مقال أصلي', 'slug' => 'original-quraniyat', 'type' => 'article', 'body' => 'محتوى المقال', 'status' => 'draft',
        ]);
        $update->assertRedirect(route('admin.quraniyat.index'))->assertSessionHasNoErrors();

        $this->actingAs($admin)->post(route('admin.quraniyat.publish', $item))->assertRedirect();
        $this->assertSame('published', $item->fresh()->status);

        $this->actingAs($admin)->post(route('admin.quraniyat.unpublish', $item))->assertRedirect();
        $this->assertSame('unpublished', $item->fresh()->status);

        $this->actingAs($admin)->delete(route('admin.quraniyat.destroy', $item))->assertRedirect();
        $this->assertSoftDeleted('content_items', ['id' => $item->id]);

        $this->actingAs($admin)->post(route('admin.quraniyat.restore', $item->id))->assertRedirect();
        $this->assertDatabaseHas('content_items', ['id' => $item->id, 'deleted_at' => null]);
    }

    public function test_admin_can_update_delete_restore_publish_and_unpublish_a_quran_centrality_item(): void
    {
        $admin = $this->userWithPermissions(['content.view', 'content.create', 'content.update', 'content.delete']);
        $category = Category::create(['name' => 'مركزية القرآن', 'slug' => 'quran-centrality']);

        $item = ContentItem::create(['type' => 'article', 'title' => 'دراسة أصلية', 'slug' => 'original-qc', 'status' => 'draft', 'author_id' => $admin->id]);
        $item->categories()->attach($category);

        $update = $this->actingAs($admin)->put(route('admin.quran-centrality.update', $item), [
            'title' => 'دراسة أصلية', 'slug' => 'original-qc', 'type' => 'article', 'status' => 'draft',
        ]);
        $update->assertRedirect(route('admin.quran-centrality.index'))->assertSessionHasNoErrors();

        $this->actingAs($admin)->post(route('admin.quran-centrality.publish', $item))->assertRedirect();
        $this->assertSame('published', $item->fresh()->status);

        $this->actingAs($admin)->post(route('admin.quran-centrality.unpublish', $item))->assertRedirect();
        $this->assertSame('unpublished', $item->fresh()->status);

        $this->actingAs($admin)->delete(route('admin.quran-centrality.destroy', $item))->assertRedirect();
        $this->assertSoftDeleted('content_items', ['id' => $item->id]);

        $this->actingAs($admin)->post(route('admin.quran-centrality.restore', $item->id))->assertRedirect();
        $this->assertDatabaseHas('content_items', ['id' => $item->id, 'deleted_at' => null]);
    }

    public function test_admin_can_update_delete_restore_publish_and_unpublish_a_reflection(): void
    {
        $admin = $this->userWithPermissions(['content.view', 'content.create', 'content.update', 'content.delete']);

        $item = ContentItem::create(['type' => 'reflection', 'title' => 'تأمل أصلي', 'slug' => 'original-reflection', 'status' => 'draft', 'author_id' => $admin->id]);
        Reflection::create(['content_item_id' => $item->id]);

        $update = $this->actingAs($admin)->put(route('admin.reflections.update', $item), [
            'title' => 'تأمل أصلي', 'slug' => 'original-reflection', 'body' => 'نص التأمل', 'status' => 'draft',
        ]);
        $update->assertRedirect(route('admin.reflections.index'))->assertSessionHasNoErrors();

        $this->actingAs($admin)->post(route('admin.reflections.publish', $item))->assertRedirect();
        $this->assertSame('published', $item->fresh()->status);

        $this->actingAs($admin)->post(route('admin.reflections.unpublish', $item))->assertRedirect();
        $this->assertSame('unpublished', $item->fresh()->status);

        $this->actingAs($admin)->delete(route('admin.reflections.destroy', $item))->assertRedirect();
        $this->assertSoftDeleted('content_items', ['id' => $item->id]);

        $this->actingAs($admin)->post(route('admin.reflections.restore', $item->id))->assertRedirect();
        $this->assertDatabaseHas('content_items', ['id' => $item->id, 'deleted_at' => null]);
    }

    public function test_admin_can_update_delete_restore_publish_and_unpublish_a_lecture(): void
    {
        $admin = $this->userWithPermissions(['content.view', 'content.create', 'content.update', 'content.delete']);

        $item = ContentItem::create(['type' => 'lecture', 'title' => 'محاضرة أصلية', 'slug' => 'original-lecture', 'status' => 'draft', 'author_id' => $admin->id]);
        Lecture::create(['content_item_id' => $item->id]);

        $update = $this->actingAs($admin)->put(route('admin.lectures.update', $item), [
            'title' => 'محاضرة أصلية', 'slug' => 'original-lecture', 'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'status' => 'draft',
        ]);
        $update->assertRedirect(route('admin.lectures.index'))->assertSessionHasNoErrors();

        $this->actingAs($admin)->post(route('admin.lectures.publish', $item))->assertRedirect();
        $this->assertSame('published', $item->fresh()->status);

        $this->actingAs($admin)->post(route('admin.lectures.unpublish', $item))->assertRedirect();
        $this->assertSame('unpublished', $item->fresh()->status);

        $this->actingAs($admin)->delete(route('admin.lectures.destroy', $item))->assertRedirect();
        $this->assertSoftDeleted('content_items', ['id' => $item->id]);

        $this->actingAs($admin)->post(route('admin.lectures.restore', $item->id))->assertRedirect();
        $this->assertDatabaseHas('content_items', ['id' => $item->id, 'deleted_at' => null]);
    }

    public function test_admin_can_update_delete_restore_publish_and_unpublish_a_wall_post(): void
    {
        $admin = $this->userWithPermissions(['content.view', 'content.create', 'content.update', 'content.delete']);

        $item = ContentItem::create(['type' => 'wall_post', 'title' => 'منشور أصلي', 'slug' => 'wall-original', 'body' => 'نص', 'status' => 'draft', 'author_id' => $admin->id]);
        WallPost::create(['content_item_id' => $item->id]);

        $update = $this->actingAs($admin)->put(route('admin.wall-posts.update', $item), [
            'title' => 'منشور أصلي', 'text' => 'نص محدث', 'status' => 'draft',
        ]);
        $update->assertRedirect(route('admin.wall-posts.index'))->assertSessionHasNoErrors();

        $this->actingAs($admin)->post(route('admin.wall-posts.publish', $item))->assertRedirect();
        $this->assertSame('published', $item->fresh()->status);

        $this->actingAs($admin)->post(route('admin.wall-posts.unpublish', $item))->assertRedirect();
        $this->assertSame('unpublished', $item->fresh()->status);

        $this->actingAs($admin)->delete(route('admin.wall-posts.destroy', $item))->assertRedirect();
        $this->assertSoftDeleted('content_items', ['id' => $item->id]);

        $this->actingAs($admin)->post(route('admin.wall-posts.restore', $item->id))->assertRedirect();
        $this->assertDatabaseHas('content_items', ['id' => $item->id, 'deleted_at' => null]);
    }

    public function test_admin_can_update_delete_restore_publish_and_unpublish_a_program_episode(): void
    {
        $admin = $this->userWithPermissions(['content.view', 'content.create', 'content.update', 'content.delete']);

        $program = ContentItem::create(['type' => 'program', 'title' => 'برنامج للحلقات', 'slug' => 'program-for-episodes', 'status' => 'published', 'published_at' => now(), 'author_id' => $admin->id]);
        Program::create(['content_item_id' => $program->id]);

        $episode = ContentItem::create(['type' => 'program_episode', 'title' => 'حلقة أصلية', 'slug' => 'original-episode', 'status' => 'draft', 'author_id' => $admin->id]);
        ProgramEpisode::create(['content_item_id' => $episode->id, 'program_id' => $program->id, 'episode_number' => 1]);

        $update = $this->actingAs($admin)->put(route('admin.programs.episodes.update', [$program, $episode]), [
            'title' => 'حلقة أصلية', 'slug' => 'original-episode', 'episode_number' => 1,
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'status' => 'draft',
        ]);
        $update->assertRedirect(route('admin.programs.episodes.index', $program))->assertSessionHasNoErrors();

        $this->actingAs($admin)->post(route('admin.programs.episodes.publish', [$program, $episode]))->assertRedirect();
        $this->assertSame('published', $episode->fresh()->status);

        $this->actingAs($admin)->post(route('admin.programs.episodes.unpublish', [$program, $episode]))->assertRedirect();
        $this->assertSame('unpublished', $episode->fresh()->status);

        $this->actingAs($admin)->delete(route('admin.programs.episodes.destroy', [$program, $episode]))->assertRedirect();
        $this->assertSoftDeleted('content_items', ['id' => $episode->id]);

        $this->actingAs($admin)->post(route('admin.programs.episodes.restore', [$program, $episode->id]))->assertRedirect();
        $this->assertDatabaseHas('content_items', ['id' => $episode->id, 'deleted_at' => null]);
    }

    public function test_admin_book_index_defaults_to_newest_first_with_pinned_items_always_on_top(): void
    {
        $admin = $this->userWithPermissions(['content.view']);

        $older = ContentItem::create(['type' => 'book', 'title' => 'كتاب قديم', 'slug' => 'older-book', 'status' => 'draft']);
        Book::create(['content_item_id' => $older->id, 'author_name' => 'مؤلف']);
        $older->forceFill(['created_at' => now()->subDays(5)])->save();

        $newer = ContentItem::create(['type' => 'book', 'title' => 'كتاب جديد', 'slug' => 'newer-book', 'status' => 'draft']);
        Book::create(['content_item_id' => $newer->id, 'author_name' => 'مؤلف']);

        // Oldest of the three by creation date, but pinned -- must still
        // land first, ahead of both unpinned items above it.
        $pinned = ContentItem::create(['type' => 'book', 'title' => 'كتاب مثبت', 'slug' => 'pinned-book', 'status' => 'draft', 'is_pinned' => true]);
        Book::create(['content_item_id' => $pinned->id, 'author_name' => 'مؤلف']);
        $pinned->forceFill(['created_at' => now()->subDays(10)])->save();

        $response = $this->actingAs($admin)->get(route('admin.books.index'));

        $response->assertSeeTextInOrder(['كتاب مثبت', 'كتاب جديد', 'كتاب قديم']);
    }

    public function test_soft_deleted_content_is_hidden_from_public_listing_and_show_page(): void
    {
        $item = ContentItem::create(['type' => 'book', 'title' => 'كتاب محذوف', 'slug' => 'deleted-book', 'status' => 'published', 'published_at' => now()]);
        Book::create(['content_item_id' => $item->id, 'author_name' => 'مؤلف']);
        $item->delete();

        $this->get('/books')->assertDontSee('كتاب محذوف');
        $this->get('/books/deleted-book')->assertNotFound();
    }

    public function test_unpublished_and_draft_content_returns_404_on_public_site(): void
    {
        $draft = ContentItem::create(['type' => 'book', 'title' => 'كتاب مسودة', 'slug' => 'draft-book', 'status' => 'draft']);
        Book::create(['content_item_id' => $draft->id, 'author_name' => 'مؤلف']);

        $unpublished = ContentItem::create(['type' => 'book', 'title' => 'كتاب غير منشور', 'slug' => 'unpublished-book', 'status' => 'unpublished', 'published_at' => now()->subDay()]);
        Book::create(['content_item_id' => $unpublished->id, 'author_name' => 'مؤلف']);

        $this->get('/books/draft-book')->assertNotFound();
        $this->get('/books/unpublished-book')->assertNotFound();
        $this->get('/books')->assertDontSee('كتاب مسودة')->assertDontSee('كتاب غير منشور');
    }

    public function test_published_content_is_visible_on_public_site(): void
    {
        $item = ContentItem::create(['type' => 'book', 'title' => 'كتاب منشور', 'slug' => 'published-book', 'status' => 'published', 'published_at' => now(), 'excerpt' => 'وصف الكتاب']);
        Book::create(['content_item_id' => $item->id, 'author_name' => 'مؤلف الكتاب']);

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
        Lecture::create(['content_item_id' => $lecture->id]);

        $this->actingAs($admin)->get(route('admin.books.edit', $lecture))->assertNotFound();
        $this->actingAs($admin)->delete(route('admin.books.destroy', $lecture))->assertNotFound();
    }

    public function test_category_filter_only_returns_matching_published_books(): void
    {
        $categoryA = Category::create(['name' => 'تصنيف أ', 'slug' => 'cat-a', 'is_active' => true]);
        $categoryB = Category::create(['name' => 'تصنيف ب', 'slug' => 'cat-b', 'is_active' => true]);

        $itemA = ContentItem::create(['type' => 'book', 'title' => 'كتاب أ', 'slug' => 'book-a', 'status' => 'published', 'published_at' => now()]);
        Book::create(['content_item_id' => $itemA->id, 'author_name' => 'م']);
        $itemA->categories()->attach($categoryA->id);

        $itemB = ContentItem::create(['type' => 'book', 'title' => 'كتاب ب', 'slug' => 'book-b', 'status' => 'published', 'published_at' => now()]);
        Book::create(['content_item_id' => $itemB->id, 'author_name' => 'م']);
        $itemB->categories()->attach($categoryB->id);

        $response = $this->get('/books?category_id='.$categoryA->id);
        $response->assertSee('كتاب أ')->assertDontSee('كتاب ب');
    }
}
