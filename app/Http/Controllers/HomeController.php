<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ContentItem;
use App\Search\SearchEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * The homepage aggregates ~10 separate queries and is by far the
     * most-visited page. A short TTL keeps it fast for the flood of
     * repeat/anonymous visits while still reflecting new content within
     * a few minutes — no need to wire cache invalidation into every
     * content controller's publish/unpublish/create/update action for
     * that small a freshness window.
     */
    public function index(): View
    {
        $data = Cache::remember('home.index.data', now()->addMinutes(5), function () {
            $latest = fn (string $type, int $limit = 4) => ContentItem::published()->where('type', $type)->latest('published_at')->limit($limit)->get();

            return [
                'featured' => ContentItem::published()->where('is_featured', true)->latest('published_at')->limit(3)->get(),
                'books' => $latest('book'), 'lectures' => $latest('lecture'), 'programs' => $latest('program'),
                'reflections' => $latest('reflection'), 'wallPosts' => $latest('wall_post'),
                'quranCentrality' => ContentItem::published()->inCategory('quran-centrality')->latest('published_at')->limit(4)->get(),
                'quraniyat' => ContentItem::published()->inCategory('quraniyat')->latest('published_at')->limit(4)->get(),
                'biography' => ContentItem::published()->where('type', 'biography')->with('categories')->latest('published_at')->first(),
            ];
        });

        $data['categories'] = Category::cachedActive()->whereNull('parent_id')->take(8)->values();

        return view('home', $data);
    }

    public function search(Request $request, SearchEngine $engine): View
    {
        $query = trim((string) $request->input('q'));
        $items = $engine->search($query ?: null);

        return view('search', compact('query', 'items'));
    }
}
