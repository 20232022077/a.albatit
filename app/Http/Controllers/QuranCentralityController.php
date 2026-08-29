<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasSiblingNavigation;
use App\Http\Controllers\Concerns\ResolvesContentSlug;
use App\Models\ContentItem;
use App\Support\SiteSettings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuranCentralityController extends Controller
{
    use HasSiblingNavigation, ResolvesContentSlug;

    private const SECTION_CATEGORY_SLUG = 'quran-centrality';

    public function index(Request $request): View
    {
        $items = ContentItem::published()
            ->inCategory(self::SECTION_CATEGORY_SLUG)
            ->with(['categories', 'media'])
            ->when($request->filled('type'), fn (Builder $q) => $q->ofType($request->string('type')->toString()))
            ->search($request->string('q')->toString() ?: null)
            ->pinnedFirst()
            ->orderBy('sort_order')
            ->latest('published_at')
            ->paginate(app(SiteSettings::class)->itemsPerPage())
            ->withQueryString();

        return view('quran-centrality.index', [
            'items' => $items,
            'query' => $request->string('q')->toString(),
            'type' => $request->string('type')->toString(),
        ]);
    }

    public function show(string $slug): View|RedirectResponse
    {
        $result = $this->resolveBySlugOrRedirect(
            fn () => ContentItem::published()->inCategory(self::SECTION_CATEGORY_SLUG)->with(['categories', 'tags', 'media']),
            $slug,
            'quran-centrality.show'
        );

        if ($result instanceof RedirectResponse) {
            return $result;
        }

        $siblings = $this->siblingNavigation(
            fn () => ContentItem::published()->inCategory(self::SECTION_CATEGORY_SLUG),
            $result
        );

        return view('quran-centrality.show', ['item' => $result, ...$siblings]);
    }
}
