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
use Illuminate\Support\Facades\Cookie;
use Illuminate\View\View;

class BookController extends Controller
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
            ->ofType('book')
            ->with(['book.cover', 'categories'])
            ->when($request->filled('category_id'), fn (Builder $q) => $q->whereHas(
                'categories', fn (Builder $c) => $c->where('categories.id', $request->integer('category_id'))
            ))
            ->search($request->string('q')->toString() ?: null)
            ->orderBy($column, $direction)
            ->paginate(app(SiteSettings::class)->itemsPerPage())
            ->withQueryString();

        return view('books.index', [
            'items' => $items,
            'categories' => Category::cachedActive()->sortBy('name')->values(),
            'sortKey' => $sortKey,
            'query' => $request->string('q')->toString(),
        ]);
    }

    public function show(string $slug): View|RedirectResponse
    {
        $result = $this->resolveBySlugOrRedirect(
            fn () => ContentItem::published()->ofType('book')->with(['book.cover', 'book.pdf', 'categories', 'tags']),
            $slug,
            'books.show'
        );

        if ($result instanceof RedirectResponse) {
            return $result;
        }

        $siblings = $this->siblingNavigation(fn () => ContentItem::published()->ofType('book'), $result);

        return view('books.show', ['item' => $result, ...$siblings]);
    }

    /**
     * Toggle a like on a book, tracked per-browser via a signed cookie
     * since visitors don't have accounts. The cookie is Laravel-encrypted,
     * so it can't be forged to force an artificial decrement — a visitor
     * can only ever remove their own like or clear their cookies to like
     * again, which is an accepted, non-critical limitation for this
     * feature (no anti-abuse guarantee beyond that).
     */
    public function like(Request $request, ContentItem $item): RedirectResponse
    {
        abort_unless($item->type === 'book', 404);
        abort_unless($item->status === 'published', 404);

        $cookieName = 'liked_book_'.$item->id;
        $alreadyLiked = $request->cookie($cookieName) === '1';

        if ($alreadyLiked) {
            // Guard against the count already being 0 (e.g. an admin reset
            // it while this visitor's cookie still said "liked") — without
            // the where(), decrement() would try to write -1 into an
            // unsigned column and throw.
            ContentItem::whereKey($item->id)->where('likes_count', '>', 0)->decrement('likes_count');
            Cookie::queue(Cookie::forget($cookieName));
        } else {
            $item->increment('likes_count');
            Cookie::queue(cookie($cookieName, '1', 60 * 24 * 365 * 3));
        }

        return back();
    }
}
