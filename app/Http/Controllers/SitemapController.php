<?php

namespace App\Http\Controllers;

use App\Models\ContentItem;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    private const STATIC_URLS = [
        ['route' => 'home', 'changefreq' => 'daily', 'priority' => '1.0'],
        ['route' => 'biography.show', 'changefreq' => 'monthly', 'priority' => '0.6'],
        ['route' => 'books.index', 'changefreq' => 'daily', 'priority' => '0.8'],
        ['route' => 'lectures.index', 'changefreq' => 'daily', 'priority' => '0.8'],
        ['route' => 'programs.index', 'changefreq' => 'daily', 'priority' => '0.8'],
        ['route' => 'reflections.index', 'changefreq' => 'daily', 'priority' => '0.8'],
        ['route' => 'quraniyat.index', 'changefreq' => 'daily', 'priority' => '0.8'],
        ['route' => 'quran-centrality.index', 'changefreq' => 'daily', 'priority' => '0.8'],
        ['route' => 'wall.index', 'changefreq' => 'daily', 'priority' => '0.5'],
    ];

    public function index(): Response
    {
        $urls = Cache::remember('sitemap.urls', now()->addHour(), fn () => $this->buildUrls());

        return response()
            ->view('sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    private function buildUrls(): array
    {
        $urls = [];

        foreach (self::STATIC_URLS as $entry) {
            if ($entry['route'] === 'biography.show' && ! ContentItem::published()->where('type', 'biography')->exists()) {
                continue;
            }

            $urls[] = [
                'loc' => route($entry['route']),
                'lastmod' => null,
                'changefreq' => $entry['changefreq'],
                'priority' => $entry['priority'],
            ];
        }

        $typeRoutes = [
            'book' => 'books.show',
            'lecture' => 'lectures.show',
            'reflection' => 'reflections.show',
            'program' => 'programs.show',
        ];

        foreach ($typeRoutes as $type => $routeName) {
            ContentItem::published()->ofType($type)->select(['id', 'slug', 'updated_at'])
                ->orderBy('id')
                ->chunk(200, function ($items) use (&$urls, $routeName) {
                    foreach ($items as $item) {
                        $urls[] = [
                            'loc' => route($routeName, $item->slug),
                            'lastmod' => $item->updated_at?->toAtomString(),
                            'changefreq' => 'weekly',
                            'priority' => '0.7',
                        ];
                    }
                });
        }

        foreach (['quraniyat' => 'quraniyat.show', 'quran-centrality' => 'quran-centrality.show'] as $categorySlug => $routeName) {
            ContentItem::published()->inCategory($categorySlug)->select(['id', 'slug', 'updated_at'])
                ->orderBy('id')
                ->chunk(200, function ($items) use (&$urls, $routeName) {
                    foreach ($items as $item) {
                        $urls[] = [
                            'loc' => route($routeName, $item->slug),
                            'lastmod' => $item->updated_at?->toAtomString(),
                            'changefreq' => 'weekly',
                            'priority' => '0.7',
                        ];
                    }
                });
        }

        return $urls;
    }
}
