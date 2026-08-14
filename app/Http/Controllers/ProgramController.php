<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesContentSlug;
use App\Models\ContentItem;
use App\Support\SiteSettings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProgramController extends Controller
{
    use ResolvesContentSlug;

    public function index(Request $request): View
    {
        $items = ContentItem::published()
            ->ofType('program')
            ->with(['program', 'categories', 'media'])
            ->search($request->string('q')->toString() ?: null)
            ->latest('published_at')
            ->paginate(app(SiteSettings::class)->itemsPerPage())
            ->withQueryString();

        return view('programs.index', [
            'items' => $items,
            'query' => $request->string('q')->toString(),
        ]);
    }

    public function show(string $slug): View|RedirectResponse
    {
        $result = $this->resolveBySlugOrRedirect(
            fn () => ContentItem::published()->ofType('program')->with(['program', 'categories', 'tags', 'media']),
            $slug,
            'programs.show'
        );

        if ($result instanceof RedirectResponse) {
            return $result;
        }

        $item = $result;

        $episodes = ContentItem::published()
            ->ofType('program_episode')
            ->whereHas('programEpisode', fn (Builder $q) => $q->where('program_id', $item->id))
            ->with(['programEpisode', 'media'])
            ->get()
            ->sortBy(fn (ContentItem $episode) => $episode->programEpisode?->episode_number ?? 0)
            ->values();

        return view('programs.show', ['item' => $item, 'episodes' => $episodes]);
    }
}
