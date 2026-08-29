<?php

namespace App\Http\Controllers;

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
     * repeat/anonymous visits; ManagesContentItems::recordActivity() (and
     * BiographyController::update()) forget this key on every content
     * mutation, so edits still show up immediately rather than waiting
     * out the TTL.
     */
    public function index(): View
    {
        $data = Cache::remember('home.index.data', now()->addMinutes(5), function () {
            // Eager load whichever relation each type's card needs for its
            // cover image and type-specific metadata (see
            // resources/views/home.blade.php) — without this, every card
            // would trigger its own extra query.
            $latest = fn (string $type, int $limit = 4) => ContentItem::published()->where('type', $type)
                ->with(match ($type) {
                    'book' => 'book.cover',
                    'program' => ['program', 'media'],
                    'lecture' => ['lecture', 'media'],
                    'wall_post' => ['wallPost', 'media'],
                    default => 'media',
                })
                ->pinnedFirst()->latest('published_at')->limit($limit)->get();

            return [
                'books' => $latest('book'), 'lectures' => $latest('lecture'), 'programs' => $latest('program'),
                'reflections' => $latest('reflection'), 'wallPosts' => $latest('wall_post'),
                'quranCentrality' => ContentItem::published()->inCategory('quran-centrality')->with('media')->pinnedFirst()->orderBy('sort_order')->latest('published_at')->limit(4)->get(),
                'quraniyat' => ContentItem::published()->inCategory('quraniyat')->with('media')->pinnedFirst()->latest('published_at')->limit(4)->get(),
                'biography' => ContentItem::published()->where('type', 'biography')->with('categories', 'biography.profileImage')->latest('published_at')->first(),
            ];
        });

        return view('home', $data);
    }

    public function search(Request $request, SearchEngine $engine): View
    {
        $query = trim((string) $request->input('q'));
        $items = $engine->search($query ?: null);

        return view('search', compact('query', 'items'));
    }
}
