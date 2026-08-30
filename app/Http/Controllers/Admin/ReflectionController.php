<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ManagesContentItems;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreReflectionRequest;
use App\Http\Requests\Admin\UpdateReflectionRequest;
use App\Models\Category;
use App\Models\ContentItem;
use App\Models\Reflection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReflectionController extends Controller
{
    use ManagesContentItems;

    private const SORTABLE = ['sort_order', 'title', 'created_at', 'published_at'];

    public function index(Request $request): View
    {
        $this->authorize('permission', 'content.view');

        $sort = in_array($request->string('sort')->toString(), self::SORTABLE, true) ? $request->string('sort')->toString() : 'created_at';
        $dir = $request->string('dir')->toString() === 'asc' ? 'asc' : 'desc';

        $items = ContentItem::query()
            ->ofType('reflection')
            ->with(['categories', 'media'])
            ->when($request->boolean('trashed'), fn (Builder $q) => $q->onlyTrashed())
            ->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->string('status')->toString()))
            ->when($request->filled('category_id'), fn (Builder $q) => $q->whereHas(
                'categories', fn (Builder $c) => $c->where('categories.id', $request->integer('category_id'))
            ))
            ->search($request->string('q')->toString() ?: null)
            ->pinnedFirst()
            ->orderBy($sort, $dir)
            ->paginate(20)
            ->withQueryString();

        return view('admin.reflections.index', [
            'items' => $items,
            'categories' => Category::orderBy('name')->get(),
            'sort' => $sort,
            'dir' => $dir,
        ]);
    }

    public function create(): View
    {
        $this->authorize('permission', 'content.create');

        return view('admin.reflections.form', [
            'item' => new ContentItem(['status' => 'draft', 'sort_order' => 0]),
            'reflection' => new Reflection,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function store(StoreReflectionRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $item = DB::transaction(function () use ($data, $request) {
            $item = ContentItem::create([
                'author_id' => $request->user()->id,
                'type' => 'reflection',
                'title' => $data['title'],
                'slug' => $this->resolveSlug($data['slug'] ?? null, $data['title']),
                'excerpt' => $data['excerpt'] ?? null,
                'body' => $data['body'] ?? null,
                'status' => $data['status'],
                'is_featured' => $request->boolean('is_featured'),
                'is_pinned' => $request->boolean('is_pinned'),
                'sort_order' => $data['sort_order'] ?? 0,
                'published_at' => $this->resolvePublishedAt($data['status'], $data['published_at'] ?? null, null),
                'meta' => $this->buildSeoMeta($data),
            ]);

            Reflection::create([
                'content_item_id' => $item->id,
                'surah_number' => $data['surah_number'] ?? null,
                'ayah_from' => $data['ayah_from'] ?? null,
                'ayah_to' => $data['ayah_to'] ?? null,
            ]);

            $this->syncCategories($item, $data['category_ids'] ?? []);
            $this->syncTags($item, $data['tags'] ?? '');
            $this->replaceMediaCollection($item, $request->file('cover_image'), 'cover', 'reflections', $request->user()->id);

            return $item;
        });

        $this->recordActivity('reflections.created', $item);

        return redirect()->route('admin.reflections.index')->with('status', 'تم إنشاء التأمل.');
    }

    public function edit(ContentItem $item): View
    {
        $this->authorizeReflectionItem($item);
        $item->load(['categories', 'tags', 'media']);

        return view('admin.reflections.form', [
            'item' => $item,
            'reflection' => $item->reflection ?? new Reflection,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateReflectionRequest $request, ContentItem $item): RedirectResponse
    {
        $this->authorizeReflectionItem($item);
        $data = $request->validated();

        DB::transaction(function () use ($data, $request, $item) {
            $item->update([
                'title' => $data['title'],
                'slug' => $this->resolveSlug($data['slug'] ?? null, $data['title'], $item),
                'excerpt' => $data['excerpt'] ?? null,
                'body' => $data['body'] ?? null,
                'status' => $data['status'],
                'is_featured' => $request->boolean('is_featured'),
                'is_pinned' => $request->boolean('is_pinned'),
                'sort_order' => $data['sort_order'] ?? 0,
                'published_at' => $this->resolvePublishedAt($data['status'], $data['published_at'] ?? null, $item->published_at),
                'meta' => $this->buildSeoMeta($data),
            ]);

            $reflection = $item->reflection ?? Reflection::create(['content_item_id' => $item->id]);
            $reflection->update([
                'surah_number' => $data['surah_number'] ?? null,
                'ayah_from' => $data['ayah_from'] ?? null,
                'ayah_to' => $data['ayah_to'] ?? null,
            ]);

            $this->syncCategories($item, $data['category_ids'] ?? []);
            $this->syncTags($item, $data['tags'] ?? '');
            $this->replaceMediaCollection($item, $request->file('cover_image'), 'cover', 'reflections', $request->user()->id, $request->boolean('remove_cover_image'));
        });

        $this->recordActivity('reflections.updated', $item);

        return redirect()->route('admin.reflections.index')->with('status', 'تم تحديث التأمل.');
    }

    public function destroy(ContentItem $item): RedirectResponse
    {
        $this->authorizeReflectionItem($item, 'content.delete');
        $item->delete();
        $this->recordActivity('reflections.deleted', $item);

        return redirect()->route('admin.reflections.index')->with('status', 'تم نقل التأمل إلى المحذوفات.');
    }

    public function restore(int $id): RedirectResponse
    {
        $this->authorize('permission', 'content.update');
        $item = ContentItem::onlyTrashed()->where('type', 'reflection')->findOrFail($id);
        $item->restore();
        $this->recordActivity('reflections.restored', $item);

        return redirect()->route('admin.reflections.index', ['trashed' => 1])->with('status', 'تمت استعادة التأمل.');
    }

    public function publish(ContentItem $item): RedirectResponse
    {
        $this->authorizeReflectionItem($item);
        $item->update(['status' => 'published', 'published_at' => $item->published_at ?? now()]);
        $this->recordActivity('reflections.published', $item);

        return back()->with('status', 'تم نشر التأمل.');
    }

    public function unpublish(ContentItem $item): RedirectResponse
    {
        $this->authorizeReflectionItem($item);
        $item->update(['status' => 'unpublished']);
        $this->recordActivity('reflections.unpublished', $item);

        return back()->with('status', 'تم إلغاء نشر التأمل.');
    }

    private function authorizeReflectionItem(ContentItem $item, string $permission = 'content.update'): void
    {
        $this->authorize('permission', $permission);
        abort_unless($item->type === 'reflection', 404);
    }
}
