<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ContentItem;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $latest = fn (string $type, int $limit = 4) => ContentItem::published()->where('type', $type)->latest('published_at')->limit($limit)->get();

        return view('home', [
            'featured' => ContentItem::published()->where('is_featured', true)->latest('published_at')->limit(3)->get(),
            'books' => $latest('book'), 'lectures' => $latest('lecture'), 'programs' => $latest('program'),
            'reflections' => $latest('reflection'), 'wallPosts' => $latest('wall_post'),
            'quranCentrality' => ContentItem::published()->inCategory('quran-centrality')->latest('published_at')->limit(4)->get(),
            'quraniyat' => ContentItem::published()->inCategory('quraniyat')->latest('published_at')->limit(4)->get(),
            'categories' => Category::active()->whereNull('parent_id')->orderBy('sort_order')->limit(8)->get(),
            'biography' => ContentItem::published()->where('type', 'biography')->with('categories')->latest('published_at')->first(),
        ]);
    }

    public function search(Request $request): View
    {
        $query = trim((string) $request->input('q'));
        $items = ContentItem::published()->search($query ?: null)->latest('published_at')->paginate(12)->withQueryString();

        return view('search', compact('query', 'items'));
    }
}
