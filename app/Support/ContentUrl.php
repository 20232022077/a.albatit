<?php

namespace App\Support;

use App\Models\ContentItem;

/**
 * Single source of truth for "where does this content item link to
 * publicly". Used anywhere a card/result needs to be a real link (search
 * results, homepage teasers) so every listing resolves URLs the same way.
 */
class ContentUrl
{
    public static function for(ContentItem $item): string
    {
        return match (true) {
            $item->type === 'book' => route('books.show', $item->slug),
            $item->type === 'lecture' => route('lectures.show', $item->slug),
            $item->type === 'reflection' => route('reflections.show', $item->slug),
            $item->type === 'wall_post' => route('wall.index'),
            $item->type === 'program' => route('programs.show', $item->slug),
            $item->type === 'program_episode' => self::episodeUrl($item),
            $item->relationLoaded('categories') && $item->categories->contains('slug', 'quran-centrality') => route('quran-centrality.show', $item->slug),
            $item->relationLoaded('categories') && $item->categories->contains('slug', 'quraniyat') => route('quraniyat.show', $item->slug),
            default => route('home'),
        };
    }

    private static function episodeUrl(ContentItem $item): string
    {
        $program = $item->programEpisode?->program?->contentItem;

        return $program ? route('programs.show', $program->slug) : route('programs.index');
    }
}
