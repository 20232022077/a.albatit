<?php

namespace App\Search;

use App\Models\ContentItem;
use App\Support\ContentUrl;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Default search implementation: queries content_items directly using the
 * MySQL FULLTEXT index (see ContentItem::scopeSearch()). Only ever touches
 * the database — no external service — as required for this first version.
 */
class EloquentSearchEngine implements SearchEngine
{
    /**
     * Content types that map 1:1 to a section of the site. Quran Centrality
     * and Quraniyat are not distinguished by `type` (they share values like
     * "article"/"video"/"pdf") but by category membership instead, so they
     * are matched separately below.
     */
    private const SECTION_TYPES = ['book', 'lecture', 'program', 'program_episode', 'reflection', 'wall_post'];

    private const CATEGORY_SECTIONS = ['quran-centrality', 'quraniyat'];

    private const TYPE_LABELS = [
        'book' => 'كتاب',
        'lecture' => 'محاضرة',
        'program' => 'برنامج',
        'program_episode' => 'حلقة برنامج',
        'reflection' => 'تأمل',
        'wall_post' => 'حائط',
    ];

    private const CATEGORY_LABELS = [
        'quran-centrality' => 'مركزية القرآن',
        'quraniyat' => 'قرآنيات',
    ];

    public function search(?string $query, int $perPage = 12): LengthAwarePaginator
    {
        $paginator = ContentItem::query()
            ->published()
            ->where(function (Builder $q) {
                $q->whereIn('type', self::SECTION_TYPES)
                    ->orWhereHas('categories', fn (Builder $c) => $c->whereIn('slug', self::CATEGORY_SECTIONS));
            })
            ->when(filled($query), fn (Builder $q) => $q->search($query))
            ->with([
                'categories:id,slug,name',
                'media' => fn ($q) => $q->wherePivot('collection', 'cover'),
                'programEpisode.program.contentItem:id,slug',
            ])
            ->latest('published_at')
            ->paginate($perPage)
            ->withQueryString();

        return $paginator->through(fn (ContentItem $item) => $this->toResult($item));
    }

    private function toResult(ContentItem $item): SearchResult
    {
        $sectionSlug = $this->sectionSlug($item);

        return new SearchResult(
            title: $item->title,
            typeLabel: self::TYPE_LABELS[$item->type] ?? self::CATEGORY_LABELS[$sectionSlug] ?? $item->type,
            excerpt: $item->excerpt ?: (filled($item->body) ? Str::limit(strip_tags((string) $item->body), 150) : null),
            imageUrl: $this->coverUrl($item),
            url: ContentUrl::for($item),
            publishedAt: $item->published_at,
        );
    }

    private function sectionSlug(ContentItem $item): ?string
    {
        foreach (self::CATEGORY_SECTIONS as $slug) {
            if ($item->categories->contains('slug', $slug)) {
                return $slug;
            }
        }

        return null;
    }

    private function coverUrl(ContentItem $item): ?string
    {
        $cover = $item->media->first();

        if (! $cover) {
            return null;
        }

        return $cover->displayUrl();
    }
}
