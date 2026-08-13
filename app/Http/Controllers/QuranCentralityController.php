<?php

namespace App\Http\Controllers;

use App\Models\ContentItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuranCentralityController extends Controller
{
    private const SECTION_CATEGORY_SLUG = 'quran-centrality';

    public function index(Request $request): View
    {
        $items = ContentItem::published()
            ->inCategory(self::SECTION_CATEGORY_SLUG)
            ->with(['categories', 'media'])
            ->when($request->filled('type'), fn (Builder $q) => $q->ofType($request->string('type')->toString()))
            ->search($request->string('q')->toString() ?: null)
            ->orderBy('sort_order')
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString();

        return view('quran-centrality.index', [
            'items' => $items,
            'query' => $request->string('q')->toString(),
            'type' => $request->string('type')->toString(),
        ]);
    }

    public function show(ContentItem $item): View
    {
        $item->load(['categories', 'tags', 'media']);
        abort_unless($item->status === 'published' && $item->categories->contains('slug', self::SECTION_CATEGORY_SLUG), 404);

        return view('quran-centrality.show', ['item' => $item]);
    }
}
