<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ManagesContentItems;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreWallPostRequest;
use App\Http\Requests\Admin\UpdateWallPostRequest;
use App\Models\ContentItem;
use App\Models\WallPost;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WallPostController extends Controller
{
    use ManagesContentItems;

    private const SORTABLE = ['sort_order', 'created_at', 'published_at'];

    public function index(Request $request): View
    {
        $this->authorize('permission', 'content.view');

        $sort = in_array($request->string('sort')->toString(), self::SORTABLE, true) ? $request->string('sort')->toString() : 'created_at';
        $dir = $request->string('dir')->toString() === 'asc' ? 'asc' : 'desc';

        $items = ContentItem::query()
            ->ofType('wall_post')
            ->with(['media', 'wallPost'])
            ->when($request->boolean('trashed'), fn (Builder $q) => $q->onlyTrashed())
            ->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->string('status')->toString()))
            ->search($request->string('q')->toString() ?: null)
            ->orderBy($sort, $dir)
            ->paginate(20)
            ->withQueryString();

        return view('admin.wall-posts.index', [
            'items' => $items,
            'sort' => $sort,
            'dir' => $dir,
        ]);
    }

    public function create(): View
    {
        $this->authorize('permission', 'content.create');

        return view('admin.wall-posts.form', [
            'item' => new ContentItem(['status' => 'draft', 'sort_order' => 0]),
            'wallPost' => new WallPost,
        ]);
    }

    public function store(StoreWallPostRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $item = DB::transaction(function () use ($data, $request) {
            $item = ContentItem::create([
                'author_id' => $request->user()->id,
                'type' => 'wall_post',
                'title' => Str::limit($data['text'], 60, ''),
                'slug' => 'wall-'.Str::uuid(),
                'body' => $data['text'],
                'status' => $data['status'],
                'is_featured' => $request->boolean('is_featured'),
                'sort_order' => $data['sort_order'] ?? 0,
                'published_at' => $this->resolvePublishedAt($data['status'], $data['published_at'] ?? null, null),
                'meta' => $this->resolveYoutubeMeta($data['video_url'] ?? null),
            ]);

            WallPost::create([
                'content_item_id' => $item->id,
                'is_pinned' => $request->boolean('is_pinned'),
            ]);

            $this->replaceMediaCollection($item, $request->file('cover_image'), 'cover', 'wall', $request->user()->id);

            return $item;
        });

        $this->recordActivity('wall_posts.created', $item);

        return redirect()->route('admin.wall-posts.index')->with('status', 'تم نشر المنشور.');
    }

    public function edit(ContentItem $item): View
    {
        $this->authorizeWallItem($item);
        $item->load('media');

        return view('admin.wall-posts.form', [
            'item' => $item,
            'wallPost' => $item->wallPost ?? new WallPost,
        ]);
    }

    public function update(UpdateWallPostRequest $request, ContentItem $item): RedirectResponse
    {
        $this->authorizeWallItem($item);
        $data = $request->validated();

        DB::transaction(function () use ($data, $request, $item) {
            $item->update([
                'title' => Str::limit($data['text'], 60, ''),
                'body' => $data['text'],
                'status' => $data['status'],
                'is_featured' => $request->boolean('is_featured'),
                'sort_order' => $data['sort_order'] ?? 0,
                'published_at' => $this->resolvePublishedAt($data['status'], $data['published_at'] ?? null, $item->published_at),
                'meta' => $this->resolveYoutubeMeta($data['video_url'] ?? null),
            ]);

            $wallPost = $item->wallPost ?? WallPost::create(['content_item_id' => $item->id]);
            $wallPost->update(['is_pinned' => $request->boolean('is_pinned')]);

            $this->replaceMediaCollection($item, $request->file('cover_image'), 'cover', 'wall', $request->user()->id);
        });

        $this->recordActivity('wall_posts.updated', $item);

        return redirect()->route('admin.wall-posts.index')->with('status', 'تم تحديث المنشور.');
    }

    public function destroy(ContentItem $item): RedirectResponse
    {
        $this->authorizeWallItem($item, 'content.delete');
        $item->delete();
        $this->recordActivity('wall_posts.deleted', $item);

        return redirect()->route('admin.wall-posts.index')->with('status', 'تم نقل المنشور إلى المحذوفات.');
    }

    public function restore(int $id): RedirectResponse
    {
        $this->authorize('permission', 'content.update');
        $item = ContentItem::onlyTrashed()->where('type', 'wall_post')->findOrFail($id);
        $item->restore();
        $this->recordActivity('wall_posts.restored', $item);

        return redirect()->route('admin.wall-posts.index', ['trashed' => 1])->with('status', 'تمت استعادة المنشور.');
    }

    public function publish(ContentItem $item): RedirectResponse
    {
        $this->authorizeWallItem($item);
        $item->update(['status' => 'published', 'published_at' => $item->published_at ?? now()]);
        $this->recordActivity('wall_posts.published', $item);

        return back()->with('status', 'تم نشر المنشور.');
    }

    public function unpublish(ContentItem $item): RedirectResponse
    {
        $this->authorizeWallItem($item);
        $item->update(['status' => 'unpublished']);
        $this->recordActivity('wall_posts.unpublished', $item);

        return back()->with('status', 'تم إلغاء نشر المنشور.');
    }

    private function authorizeWallItem(ContentItem $item, string $permission = 'content.update'): void
    {
        $this->authorize('permission', $permission);
        abort_unless($item->type === 'wall_post', 404);
    }
}
