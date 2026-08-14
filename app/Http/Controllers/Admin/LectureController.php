<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ManagesContentItems;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLectureRequest;
use App\Http\Requests\Admin\UpdateLectureRequest;
use App\Models\Category;
use App\Models\ContentItem;
use App\Models\Lecture;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LectureController extends Controller
{
    use ManagesContentItems;

    private const SORTABLE = ['sort_order', 'title', 'created_at', 'published_at'];

    public function index(Request $request): View
    {
        $this->authorize('permission', 'content.view');

        $sort = in_array($request->string('sort')->toString(), self::SORTABLE, true) ? $request->string('sort')->toString() : 'sort_order';
        $dir = $request->string('dir')->toString() === 'desc' ? 'desc' : 'asc';

        $items = ContentItem::query()
            ->ofType('lecture')
            ->with(['categories', 'media'])
            ->when($request->boolean('trashed'), fn (Builder $q) => $q->onlyTrashed())
            ->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->string('status')->toString()))
            ->when($request->filled('category_id'), fn (Builder $q) => $q->whereHas(
                'categories', fn (Builder $c) => $c->where('categories.id', $request->integer('category_id'))
            ))
            ->search($request->string('q')->toString() ?: null)
            ->orderBy($sort, $dir)
            ->paginate(20)
            ->withQueryString();

        return view('admin.lectures.index', [
            'items' => $items,
            'categories' => Category::orderBy('name')->get(),
            'sort' => $sort,
            'dir' => $dir,
        ]);
    }

    public function create(): View
    {
        $this->authorize('permission', 'content.create');

        return view('admin.lectures.form', [
            'item' => new ContentItem(['status' => 'draft', 'sort_order' => 0]),
            'lecture' => new Lecture,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function store(StoreLectureRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $item = DB::transaction(function () use ($data, $request) {
            $item = ContentItem::create([
                'author_id' => $request->user()->id,
                'type' => 'lecture',
                'title' => $data['title'],
                'slug' => $this->resolveSlug($data['slug'] ?? null, $data['title']),
                'excerpt' => $data['excerpt'] ?? null,
                'body' => $data['body'] ?? null,
                'status' => $data['status'],
                'is_featured' => $request->boolean('is_featured'),
                'sort_order' => $data['sort_order'] ?? 0,
                'published_at' => $this->resolvePublishedAt($data['status'], $data['published_at'] ?? null, null),
                'meta' => $this->buildSeoMeta($data, $this->resolveYoutubeMeta($data['video_url'])),
            ]);

            Lecture::create([
                'content_item_id' => $item->id,
                'speaker' => $data['speaker'] ?? null,
                'delivered_at' => $data['delivered_at'] ?? null,
                'venue' => $data['venue'] ?? null,
            ]);

            $this->syncCategories($item, $data['category_ids'] ?? []);
            $this->syncTags($item, $data['tags'] ?? '');
            $this->replaceMediaCollection($item, $request->file('cover_image'), 'cover', 'lectures', $request->user()->id);

            return $item;
        });

        $this->recordActivity('lectures.created', $item);

        return redirect()->route('admin.lectures.index')->with('status', 'تم إنشاء المحاضرة.');
    }

    public function edit(ContentItem $item): View
    {
        $this->authorizeLectureItem($item);
        $item->load(['categories', 'tags', 'media']);

        return view('admin.lectures.form', [
            'item' => $item,
            'lecture' => $item->lecture ?? new Lecture,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateLectureRequest $request, ContentItem $item): RedirectResponse
    {
        $this->authorizeLectureItem($item);
        $data = $request->validated();

        DB::transaction(function () use ($data, $request, $item) {
            $item->update([
                'title' => $data['title'],
                'slug' => $this->resolveSlug($data['slug'] ?? null, $data['title'], $item),
                'excerpt' => $data['excerpt'] ?? null,
                'body' => $data['body'] ?? null,
                'status' => $data['status'],
                'is_featured' => $request->boolean('is_featured'),
                'sort_order' => $data['sort_order'] ?? 0,
                'published_at' => $this->resolvePublishedAt($data['status'], $data['published_at'] ?? null, $item->published_at),
                'meta' => $this->buildSeoMeta($data, $this->resolveYoutubeMeta($data['video_url'])),
            ]);

            $lecture = $item->lecture ?? Lecture::create(['content_item_id' => $item->id]);
            $lecture->update([
                'speaker' => $data['speaker'] ?? null,
                'delivered_at' => $data['delivered_at'] ?? null,
                'venue' => $data['venue'] ?? null,
            ]);

            $this->syncCategories($item, $data['category_ids'] ?? []);
            $this->syncTags($item, $data['tags'] ?? '');
            $this->replaceMediaCollection($item, $request->file('cover_image'), 'cover', 'lectures', $request->user()->id);
        });

        $this->recordActivity('lectures.updated', $item);

        return redirect()->route('admin.lectures.index')->with('status', 'تم تحديث المحاضرة.');
    }

    public function destroy(ContentItem $item): RedirectResponse
    {
        $this->authorizeLectureItem($item, 'content.delete');
        $item->delete();
        $this->recordActivity('lectures.deleted', $item);

        return redirect()->route('admin.lectures.index')->with('status', 'تم نقل المحاضرة إلى المحذوفات.');
    }

    public function restore(int $id): RedirectResponse
    {
        $this->authorize('permission', 'content.update');
        $item = ContentItem::onlyTrashed()->where('type', 'lecture')->findOrFail($id);
        $item->restore();
        $this->recordActivity('lectures.restored', $item);

        return redirect()->route('admin.lectures.index', ['trashed' => 1])->with('status', 'تمت استعادة المحاضرة.');
    }

    public function publish(ContentItem $item): RedirectResponse
    {
        $this->authorizeLectureItem($item);
        $item->update(['status' => 'published', 'published_at' => $item->published_at ?? now()]);
        $this->recordActivity('lectures.published', $item);

        return back()->with('status', 'تم نشر المحاضرة.');
    }

    public function unpublish(ContentItem $item): RedirectResponse
    {
        $this->authorizeLectureItem($item);
        $item->update(['status' => 'unpublished']);
        $this->recordActivity('lectures.unpublished', $item);

        return back()->with('status', 'تم إلغاء نشر المحاضرة.');
    }

    private function authorizeLectureItem(ContentItem $item, string $permission = 'content.update'): void
    {
        $this->authorize('permission', $permission);
        abort_unless($item->type === 'lecture', 404);
    }

    private function syncCategories(ContentItem $item, array $categoryIds): void
    {
        $item->categories()->sync($categoryIds);
    }
}
