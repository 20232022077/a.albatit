<?php

namespace App\Http\Controllers;

use App\Models\ContentItem;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WallController extends Controller
{
    public function index(Request $request): View
    {
        $items = ContentItem::published()
            ->ofType('wall_post')
            ->with(['media', 'wallPost'])
            ->search($request->string('q')->toString() ?: null)
            ->orderBy('sort_order')
            ->latest('published_at')
            ->paginate(20)
            ->withQueryString();

        return view('wall.index', [
            'items' => $items,
            'query' => $request->string('q')->toString(),
        ]);
    }
}
