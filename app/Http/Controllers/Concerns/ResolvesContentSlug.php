<?php

namespace App\Http\Controllers\Concerns;

use App\Models\ContentItem;
use App\Models\ContentItemSlug;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;

trait ResolvesContentSlug
{
    /**
     * Resolve a content item by its current slug within the given scope.
     * If nothing currently matches but the slug used to belong to an item
     * in this same scope (it was renamed since), issue a permanent
     * redirect to that item's current URL instead of a dead 404 — this is
     * the whole point of tracking slug history: old links keep working.
     *
     * @param  Closure(): Builder  $scope  Builds the base query (type/category/published, etc.)
     */
    protected function resolveBySlugOrRedirect(Closure $scope, string $slug, string $routeName): ContentItem|RedirectResponse
    {
        $item = $scope()->where('slug', $slug)->first();
        if ($item) {
            return $item;
        }

        $contentItemId = ContentItemSlug::where('slug', $slug)->value('content_item_id');
        if ($contentItemId) {
            $current = $scope()->whereKey($contentItemId)->first();
            if ($current) {
                return redirect()->route($routeName, $current->slug, 301);
            }
        }

        abort(404);
    }
}
