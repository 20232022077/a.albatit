<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasSiblingNavigation;
use App\Http\Controllers\Concerns\ResolvesContentSlug;
use App\Models\Category;
use App\Models\ContentItem;
use App\Support\SiteSettings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LectureController extends Controller
{
    use HasSiblingNavigation, ResolvesContentSlug;

    private const SORTS = [
        'newest' => ['published_at', 'desc'],
        'oldest' => ['published_at', 'asc'],
        'title' => ['title', 'asc'],
    ];

    public function index(Request $request): View
    {
        $sortKey = array_key_exists($request->string('sort')->toString(), self::SORTS) ? $request->string('sort')->toString() : 'newest';
        [$column, $direction] = self::SORTS[$sortKey];

        $items = ContentItem::published()
            ->ofType('lecture')
            ->with(['categories', 'media', 'lecture'])
            ->when($request->filled('category_id'), fn (Builder $q) => $q->whereHas(
                'categories', fn (Builder $c) => $c->where('categories.id', $request->integer('category_id'))
            ))
            ->search($request->string('q')->toString() ?: null)
            ->pinnedFirst()
            ->orderBy($column, $direction)
            ->paginate(app(SiteSettings::class)->itemsPerPage())
            ->withQueryString();

        return view('lectures.index', [
            'items' => $items,
            'categories' => Category::cachedActive()->sortBy('name')->values(),
            'sortKey' => $sortKey,
            'query' => $request->string('q')->toString(),
        ]);
    }

    public function show(string $slug): View|RedirectResponse
    {
        $result = $this->resolveBySlugOrRedirect(
            fn () => ContentItem::published()->ofType('lecture')->with(['lecture', 'categories', 'tags', 'media']),
            $slug,
            'lectures.show'
        );

        if ($result instanceof RedirectResponse) {
            return $result;
        }

        $siblings = $this->siblingNavigation(fn () => ContentItem::published()->ofType('lecture'), $result);

        return view('lectures.show', ['item' => $result, ...$siblings]);
    }
}
