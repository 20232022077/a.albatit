<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ContentItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReflectionController extends Controller
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
            ->ofType('reflection')
            ->with(['categories', 'media'])
            ->when($request->filled('category_id'), fn (Builder $q) => $q->whereHas(
                'categories', fn (Builder $c) => $c->where('categories.id', $request->integer('category_id'))
            ))
            ->search($request->string('q')->toString() ?: null)
            ->orderBy($column, $direction)
            ->paginate(12)
            ->withQueryString();

        return view('reflections.index', [
            'items' => $items,
            'categories' => Category::active()->orderBy('name')->get(),
            'sortKey' => $sortKey,
            'query' => $request->string('q')->toString(),
        ]);
    }

    public function show(ContentItem $item): View
    {
        $item->load(['reflection', 'categories', 'tags', 'media']);
        abort_unless($item->type === 'reflection' && $item->status === 'published', 404);

        return view('reflections.show', ['item' => $item]);
    }
}
