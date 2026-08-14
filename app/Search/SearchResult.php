<?php

namespace App\Search;

use Illuminate\Support\Carbon;

/**
 * A normalized, presentation-ready search result. Every search engine
 * implementation (Eloquent today, a specialized engine later) must produce
 * these so the view and controller never need to know which engine ran.
 */
final class SearchResult
{
    public function __construct(
        public readonly string $title,
        public readonly string $typeLabel,
        public readonly ?string $excerpt,
        public readonly ?string $imageUrl,
        public readonly string $url,
        public readonly ?Carbon $publishedAt,
    ) {}
}
