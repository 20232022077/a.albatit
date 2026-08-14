<?php

namespace App\Search;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Contract for the site's internal search. The current implementation
 * (EloquentSearchEngine) queries the database directly; a future engine
 * (e.g. backed by Meilisearch/Algolia) can implement this same contract
 * and be swapped in via the service container binding alone — no changes
 * needed to the controller or views that consume it.
 */
interface SearchEngine
{
    /**
     * @return LengthAwarePaginator<int, SearchResult>
     */
    public function search(?string $query, int $perPage = 12): LengthAwarePaginator;
}
