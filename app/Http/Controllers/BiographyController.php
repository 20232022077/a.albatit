<?php

namespace App\Http\Controllers;

use App\Models\Biography;
use App\Models\ContentItem;
use Illuminate\View\View;

class BiographyController extends Controller
{
    public function show(): View
    {
        $biography = Biography::with(['contentItem', 'sections', 'profileImage'])->first();
        abort_unless($biography && $biography->contentItem?->status === 'published', 404);

        $books = ContentItem::published()
            ->ofType('book')
            ->whereHas('book', fn ($q) => $q->where('author_name', $biography->contentItem->title))
            ->with('book.cover', 'media')
            ->orderBy('sort_order')
            ->latest('published_at')
            ->get();

        return view('biography.show', ['biography' => $biography, 'books' => $books]);
    }
}
