<?php

namespace App\Http\Controllers\Concerns;

use App\Models\ContentItem;
use Closure;
use Illuminate\Database\Eloquent\Builder;

trait HasSiblingNavigation
{
    /**
     * Find the item immediately before (newer) and after (older) the given
     * item within the same already-scoped, published query — matching the
     * newest-first order every public listing uses, so "next" continues the
     * browsing session instead of jumping around. Read-only: two extra
     * SELECTs, no writes anywhere.
     */
    protected function siblingNavigation(Closure $scope, ContentItem $current): array
    {
        $next = $scope()
            ->where(fn (Builder $q) => $q->where('published_at', '<', $current->published_at)
                ->orWhere(fn (Builder $q2) => $q2->where('published_at', $current->published_at)->where('id', '<', $current->id)))
            ->orderByDesc('published_at')->orderByDesc('id')
            ->with('categories')
            ->first();

        $prev = $scope()
            ->where(fn (Builder $q) => $q->where('published_at', '>', $current->published_at)
                ->orWhere(fn (Builder $q2) => $q2->where('published_at', $current->published_at)->where('id', '>', $current->id)))
            ->orderBy('published_at')->orderBy('id')
            ->with('categories')
            ->first();

        return ['prev' => $prev, 'next' => $next];
    }
}
