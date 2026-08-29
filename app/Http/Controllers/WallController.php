<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasSiblingNavigation;
use App\Http\Controllers\Concerns\ResolvesContentSlug;
use App\Models\ContentItem;
use App\Support\SiteSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WallController extends Controller
{
    use HasSiblingNavigation, ResolvesContentSlug;

    public function index(Request $request): View
    {
        $items = ContentItem::published()
            ->ofType('wall_post')
            ->with(['media', 'wallPost'])
            ->search($request->string('q')->toString() ?: null)
            ->pinnedFirst()
            ->orderBy('sort_order')
            ->latest('published_at')
            ->paginate(app(SiteSettings::class)->itemsPerPage())
            ->withQueryString();

        return view('wall.index', [
            'items' => $items,
            'query' => $request->string('q')->toString(),
        ]);
    }

    public function show(string $slug): View|RedirectResponse
    {
        $result = $this->resolveBySlugOrRedirect(
            fn () => ContentItem::published()->ofType('wall_post')->with(['wallPost', 'media']),
            $slug,
            'wall.show'
        );

        if ($result instanceof RedirectResponse) {
            return $result;
        }

        $siblings = $this->siblingNavigation(fn () => ContentItem::published()->ofType('wall_post'), $result);

        return view('wall.show', ['item' => $result, ...$siblings]);
    }
}
