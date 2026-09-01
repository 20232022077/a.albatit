<?php

namespace Tests\Feature;

use App\Models\Biography;
use App\Models\Book;
use App\Models\Category;
use App\Models\ContentItem;
use App\Models\Media;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

/**
 * Real production regression: a media row can be the live file behind a
 * book cover/PDF, a biography profile photo, or a category image via a
 * plain belongsTo column (cover_media_id, pdf_media_id, profile_media_id,
 * image_media_id) — none of which are rows in the content_media pivot
 * table. Media::contentItems() alone (a belongsToMany over that pivot)
 * never sees those, so a check that only asked "does contentItems() have
 * any rows?" would let an in-use file be trashed or force-deleted while a
 * public page still renders it.
 */
class MediaProtectionTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AccessControlSeeder::class);
        Storage::fake('public');
    }

    private function createMedia(): Media
    {
        Storage::disk('public')->put('books/test-cover.jpg', 'fake-image-bytes');

        return Media::create([
            'disk' => 'public',
            'path' => 'books/test-cover.jpg',
            'original_name' => 'test-cover.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 17,
        ]);
    }

    public function test_a_media_file_used_as_a_book_cover_cannot_be_moved_to_trash(): void
    {
        $admin = $this->userWithPermissions(['content.delete']);
        $media = $this->createMedia();
        $item = ContentItem::create(['type' => 'book', 'title' => 'كتاب', 'slug' => 'ktab-test', 'status' => 'published']);
        Book::create(['content_item_id' => $item->id, 'cover_media_id' => $media->id]);

        $response = $this->actingAs($admin)->delete(route('admin.media.destroy', $media));

        $response->assertRedirect();
        $this->assertNotNull($response->getSession()->get('error'));
        $this->assertNull($media->fresh()->deleted_at);
    }

    public function test_a_media_file_used_as_a_biography_photo_cannot_be_moved_to_trash(): void
    {
        $admin = $this->userWithPermissions(['content.delete']);
        $media = $this->createMedia();
        $item = ContentItem::create(['type' => 'biography', 'title' => 'السيرة', 'slug' => 'bio-test', 'status' => 'published']);
        Biography::create(['content_item_id' => $item->id, 'profile_media_id' => $media->id]);

        $response = $this->actingAs($admin)->delete(route('admin.media.destroy', $media));

        $response->assertRedirect();
        $this->assertNotNull($response->getSession()->get('error'));
        $this->assertNull($media->fresh()->deleted_at);
    }

    public function test_a_media_file_used_as_a_category_image_cannot_be_moved_to_trash(): void
    {
        $admin = $this->userWithPermissions(['content.delete']);
        $media = $this->createMedia();
        Category::create(['name' => 'تصنيف', 'slug' => 'cat-test', 'image_media_id' => $media->id]);

        $response = $this->actingAs($admin)->delete(route('admin.media.destroy', $media));

        $response->assertRedirect();
        $this->assertNotNull($response->getSession()->get('error'));
        $this->assertNull($media->fresh()->deleted_at);
    }

    public function test_an_unused_media_file_can_still_be_moved_to_trash(): void
    {
        $admin = $this->userWithPermissions(['content.delete']);
        $media = $this->createMedia();

        $response = $this->actingAs($admin)->delete(route('admin.media.destroy', $media));

        $response->assertRedirect(route('admin.media.index'));
        $this->assertNotNull($media->fresh()->deleted_at);
    }

    public function test_a_trashed_media_file_still_referenced_by_a_book_cannot_be_force_deleted(): void
    {
        $admin = $this->userWithPermissions(['content.delete']);
        $media = $this->createMedia();
        $item = ContentItem::create(['type' => 'book', 'title' => 'كتاب', 'slug' => 'ktab-test-2', 'status' => 'published']);
        Book::create(['content_item_id' => $item->id, 'cover_media_id' => $media->id]);
        $media->delete();

        $response = $this->actingAs($admin)->delete(route('admin.media.force-destroy', $media->id));

        $response->assertRedirect();
        $this->assertNotNull($response->getSession()->get('error'));
        $this->assertModelExists($media);
        Storage::disk('public')->assertExists($media->path);
    }

    public function test_a_genuinely_orphaned_trashed_media_file_can_be_force_deleted(): void
    {
        $admin = $this->userWithPermissions(['content.delete']);
        $media = $this->createMedia();
        $media->delete();

        $response = $this->actingAs($admin)->delete(route('admin.media.force-destroy', $media->id));

        $response->assertRedirect(route('admin.media.index', ['trashed' => 1]));
        $this->assertModelMissing($media);
        Storage::disk('public')->assertMissing($media->path);
    }
}
