<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ContentItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookController extends Controller
{
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
            ->with(['book', 'categories'])
            ->when($request->filled('category_id'), fn (Builder $q) => $q->whereHas(
                'categories', fn (Builder $c) => $c->where('categories.id', $request->integer('category_id'))
            ))
            ->search($request->string('q')->toString() ?: null)
            ->orderBy($column, $direction)
            ->paginate(12)
            ->withQueryString();

        return view('books.index', [
            'items' => $items,
            'categories' => Category::orderBy('name')->get(),
            'sortKey' => $sortKey,
            'query' => $request->string('q')->toString(),
        ]);
    }

    public function show(ContentItem $item): View
    {
        $item->load(['book', 'categories', 'tags']);
        abort_unless($item->type === 'book' && $item->status === 'published', 404);

        return view('books.show', ['item' => $item]);
    }
}
