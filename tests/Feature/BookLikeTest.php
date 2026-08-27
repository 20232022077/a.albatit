<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\ContentItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookLikeTest extends TestCase
{
    use RefreshDatabase;

    private function publishedBook(): ContentItem
    {
        $item = ContentItem::create([
            'type' => 'book',
            'title' => 'كتاب اختبار الإعجاب',
            'slug' => 'like-test-book',
            'status' => 'published',
            'published_at' => now(),
        ]);
        Book::create(['content_item_id' => $item->id, 'author_name' => 'مؤلف']);

        return $item;
    }

    public function test_a_guest_can_like_a_book_and_the_count_increments(): void
    {
        $item = $this->publishedBook();

        $response = $this->post(route('books.like', $item));

        $response->assertRedirect();
        $response->assertCookie('liked_book_'.$item->id, '1');
        $this->assertSame(1, $item->fresh()->likes_count);
    }

    public function test_liking_again_from_the_same_browser_toggles_it_off(): void
    {
        $item = $this->publishedBook();

        // A real like first, so the cookie is genuine and the counter is
        // actually at 1 before we test the "unlike" half of the toggle.
        $this->post(route('books.like', $item));
        $this->assertSame(1, $item->fresh()->likes_count);

        $this->withCookie('liked_book_'.$item->id, '1')->post(route('books.like', $item));

        $this->assertSame(0, $item->fresh()->likes_count);
    }

    public function test_unliking_never_drives_the_count_below_zero(): void
    {
        $item = $this->publishedBook();

        // A forged "liked" cookie with no matching prior like (e.g. counter
        // was reset independently) must not crash on the unsigned column.
        $response = $this->withCookie('liked_book_'.$item->id, '1')->post(route('books.like', $item));

        $response->assertRedirect();
        $this->assertSame(0, $item->fresh()->likes_count);
    }

    public function test_liking_an_unpublished_book_is_rejected(): void
    {
        $item = ContentItem::create([
            'type' => 'book',
            'title' => 'كتاب غير منشور',
            'slug' => 'unpublished-like-test',
            'status' => 'draft',
        ]);
        Book::create(['content_item_id' => $item->id, 'author_name' => 'مؤلف']);

        $this->post(route('books.like', $item))->assertNotFound();
        $this->assertSame(0, $item->fresh()->likes_count);
    }

    public function test_liking_a_non_book_content_item_is_rejected(): void
    {
        $item = ContentItem::create([
            'type' => 'wall_post',
            'title' => 'منشور',
            'slug' => 'wall-like-test',
            'status' => 'published',
            'body' => 'نص',
            'published_at' => now(),
        ]);

        $this->post(route('books.like', $item))->assertNotFound();
    }
}
