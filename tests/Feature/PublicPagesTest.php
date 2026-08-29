<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\ContentItem;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    /**
     * Not RefreshDatabase: MySQL/InnoDB FULLTEXT indexes (used by search)
     * only become visible to MATCH...AGAINST queries after a real COMMIT —
     * RefreshDatabase wraps each test in an uncommitted transaction, which
     * makes freshly-inserted rows invisible to full-text search and
     * produces a false "no results" failure. Truncation commits for real.
     */
    use DatabaseTruncation;

    public function test_all_public_index_pages_load_successfully(): void
    {
        // /biography is excluded: it 404s by design until a biography
        // content item exists, which no seeder creates automatically.
        foreach (['/', '/books', '/lectures', '/programs', '/reflections', '/quraniyat', '/quran-centrality', '/wall', '/search', '/login'] as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_sitemap_and_robots_are_reachable_and_correctly_scoped(): void
    {
        $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        $robots = $this->get('/robots.txt');
        $robots->assertOk();
        $robots->assertSee('Disallow: /admin', false);
        $robots->assertSee('Disallow: /dashboard', false);
        $robots->assertSee('Sitemap:', false);
    }

    public function test_admin_and_sensitive_pages_are_marked_noindex(): void
    {
        $this->get('/login')->assertSee('noindex,nofollow', false);
        $this->get('/search')->assertSee('noindex,follow', false);
    }

    public function test_search_only_returns_published_content(): void
    {
        $published = ContentItem::create(['type' => 'book', 'title' => 'كتاب بحث منشور', 'slug' => 'search-published', 'status' => 'published', 'published_at' => now()]);
        Book::create(['content_item_id' => $published->id, 'author_name' => 'م']);

        $draft = ContentItem::create(['type' => 'book', 'title' => 'كتاب بحث مسودة', 'slug' => 'search-draft', 'status' => 'draft']);
        Book::create(['content_item_id' => $draft->id, 'author_name' => 'م']);

        $response = $this->get('/search?q='.urlencode('بحث'));
        $response->assertSee('كتاب بحث منشور')->assertDontSee('كتاب بحث مسودة');
    }

    public function test_homepage_has_no_broken_internal_links(): void
    {
        $response = $this->get('/');
        $response->assertOk();

        preg_match_all('/href="(\/[^"#]*)"/', $response->getContent(), $matches);
        $internalPaths = array_unique($matches[1]);

        $broken = [];
        foreach ($internalPaths as $path) {
            if (str_starts_with($path, '/admin') || str_starts_with($path, '/dashboard') || str_starts_with($path, '/logout')) {
                continue; // requires auth by design, not a "broken link"
            }
            $status = $this->get($path)->getStatusCode();
            if ($status >= 400) {
                $broken[] = "$path -> $status";
            }
        }

        $this->assertEmpty($broken, 'Broken internal links found on homepage: '.implode(', ', $broken));
    }

    public function test_content_page_canonical_and_og_tags_are_present(): void
    {
        $item = ContentItem::create(['type' => 'book', 'title' => 'كتاب سيو', 'slug' => 'seo-book', 'status' => 'published', 'published_at' => now(), 'excerpt' => 'وصف']);
        Book::create(['content_item_id' => $item->id, 'author_name' => 'مؤلف']);

        $response = $this->get('/books/seo-book');
        $response->assertOk();
        $response->assertSee('<link rel="canonical"', false);
        $response->assertSee('og:title', false);
        $response->assertSee('application/ld+json', false);
    }
}
