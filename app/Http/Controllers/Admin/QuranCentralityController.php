<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ManagesContentItems;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreQuranCentralityItemRequest;
use App\Http\Requests\Admin\UpdateQuranCentralityItemRequest;
use App\Models\Category;
use App\Models\ContentItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class QuranCentralityController extends Controller
{
    use ManagesContentItems;

    private const SECTION_CATEGORY_SLUG = 'quran-centrality';

    private const TYPES = ['article', 'study', 'video', 'pdf', 'image'];

    private const SORTABLE = ['sort_order', 'title', 'created_at', 'published_at'];

    private ?Category $sectionCategory = null;

    public function index(Request $request): View
    {
        $this->authorize('permission', 'content.view');

        $sort = in_array($request->string('sort')->toString(), self::SORTABLE, true) ? $request->string('sort')->toString() : 'created_at';
        $dir = $request->string('dir')->toString() === 'asc' ? 'asc' : 'desc';

        $items = ContentItem::query()
            ->inCategory(self::SECTION_CATEGORY_SLUG)
            ->with(['categories', 'tags', 'media'])
            ->when($request->boolean('trashed'), fn (Builder $q) => $q->onlyTrashed())
            ->when($request->filled('type'), fn (Builder $q) => $q->ofType($request->string('type')->toString()))
            ->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->string('status')->toString()))
            ->when($request->filled('category_id'), fn (Builder $q) => $q->whereHas(
                'categories', fn (Builder $c) => $c->where('categories.id', $request->integer('category_id'))
            ))
            ->search($request->string('q')->toString() ?: null)
            ->pinnedFirst()
            ->orderBy($sort, $dir)
            ->paginate(20)
            ->withQueryString();

        return view('admin.quran-centrality.index', [
            'items' => $items,
            'categories' => $this->categoryOptions(),
            'types' => self::TYPES,
            'sort' => $sort,
            'dir' => $dir,
        ]);
    }

    public function create(): View
    {
        $this->authorize('permission', 'content.create');

        return view('admin.quran-centrality.form', [
            'item' => new ContentItem(['type' => 'article', 'status' => 'draft', 'sort_order' => 0]),
            'categories' => $this->categoryOptions(),
            'types' => self::TYPES,
        ]);
    }

    public function store(StoreQuranCentralityItemRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $item = DB::transaction(function () use ($data, $request) {
            $item = ContentItem::create([
                'author_id' => $request->user()->id,
                'type' => $data['type'],
                'title' => $data['title'],
                'slug' => $this->resolveSlug($data['slug'] ?? null, $data['title']),
                'excerpt' => $data['excerpt'] ?? null,
                'body' => $data['body'] ?? null,
                'status' => $data['status'],
                'is_featured' => $request->boolean('is_featured'),
                'is_pinned' => $request->boolean('is_pinned'),
                'sort_order' => $data['sort_order'] ?? 0,
                'published_at' => $this->resolvePublishedAt($data['status'], $data['published_at'] ?? null, null),
                'meta' => $this->buildSeoMeta($data, $this->resolveYoutubeMeta($data['video_url'] ?? null)),
            ]);

            $this->syncCategories($item, $data['category_ids'] ?? []);
            $this->syncTags($item, $data['tags'] ?? '');
            $this->replaceMediaCollection($item, $request->file('cover_image'), 'cover', 'quran-centrality', $request->user()->id);
            $this->replaceMediaCollection($item, $request->file('attachment'), 'attachment', 'quran-centrality', $request->user()->id);

            return $item;
        });

        $this->recordActivity('quran_centrality.created', $item);

        return redirect()->route('admin.quran-centrality.index')->with('status', 'تم إنشاء المحتوى.');
    }

    public function edit(ContentItem $item): View
    {
        $this->authorizeItem('update', $item);
        $item->load(['categories', 'tags', 'media']);

        return view('admin.quran-centrality.form', [
            'item' => $item,
            'categories' => $this->categoryOptions(),
            'types' => self::TYPES,
        ]);
    }

    public function update(UpdateQuranCentralityItemRequest $request, ContentItem $item): RedirectResponse
    {
        $this->authorizeItem('update', $item);
        $data = $request->validated();

        DB::transaction(function () use ($data, $request, $item) {
            $item->update([
                'type' => $data['type'],
                'title' => $data['title'],
                'slug' => $this->resolveSlug($data['slug'] ?? null, $data['title'], $item),
                'excerpt' => $data['excerpt'] ?? null,
                'body' => $data['body'] ?? null,
                'status' => $data['status'],
                'is_featured' => $request->boolean('is_featured'),
                'is_pinned' => $request->boolean('is_pinned'),
                'sort_order' => $data['sort_order'] ?? 0,
                'published_at' => $this->resolvePublishedAt($data['status'], $data['published_at'] ?? null, $item->published_at),
                'meta' => $this->buildSeoMeta($data, $this->resolveYoutubeMeta($data['video_url'] ?? null)),
            ]);

            $this->syncCategories($item, $data['category_ids'] ?? []);
            $this->syncTags($item, $data['tags'] ?? '');
            $this->replaceMediaCollection($item, $request->file('cover_image'), 'cover', 'quran-centrality', $request->user()->id, $request->boolean('remove_cover_image'));
            $this->replaceMediaCollection($item, $request->file('attachment'), 'attachment', 'quran-centrality', $request->user()->id);
        });

        $this->recordActivity('quran_centrality.updated', $item);

        return redirect()->route('admin.quran-centrality.index')->with('status', 'تم تحديث المحتوى.');
    }

    public function destroy(ContentItem $item): RedirectResponse
    {
        $this->authorizeItem('delete', $item);
        $item->delete();
        $this->recordActivity('quran_centrality.deleted', $item);

        return redirect()->route('admin.quran-centrality.index')->with('status', 'تم نقل المحتوى إلى المحذوفات.');
    }

    public function restore(int $id): RedirectResponse
    {
        $this->authorize('permission', 'content.update');
        $item = ContentItem::onlyTrashed()->findOrFail($id);
        abort_unless($item->categories()->where('slug', self::SECTION_CATEGORY_SLUG)->exists(), 404);

        $item->restore();
        $this->recordActivity('quran_centrality.restored', $item);

        return redirect()->route('admin.quran-centrality.index', ['trashed' => 1])->with('status', 'تمت استعادة المحتوى.');
    }

    public function publish(ContentItem $item): RedirectResponse
    {
        $this->authorizeItem('update', $item);
        $item->update(['status' => 'published', 'published_at' => $item->published_at ?? now()]);
        $this->recordActivity('quran_centrality.published', $item);

        return back()->with('status', 'تم نشر المحتوى.');
    }

    public function unpublish(ContentItem $item): RedirectResponse
    {
        $this->authorizeItem('update', $item);
        $item->update(['status' => 'unpublished']);
        $this->recordActivity('quran_centrality.unpublished', $item);

        return back()->with('status', 'تم إلغاء نشر المحتوى.');
    }

    private function authorizeItem(string $ability, ContentItem $item): void
    {
        $permission = match ($ability) {
            'update' => 'content.update',
            'delete' => 'content.delete',
            default => 'content.view',
        };
        $this->authorize('permission', $permission);
        abort_unless($item->categories()->where('slug', self::SECTION_CATEGORY_SLUG)->exists(), 404);
    }

    private function sectionCategory(): Category
    {
        return $this->sectionCategory ??= Category::where('slug', self::SECTION_CATEGORY_SLUG)->firstOrFail();
    }

    private function categoryOptions()
    {
        $section = $this->sectionCategory();

        return Category::where('id', $section->id)->orWhere('parent_id', $section->id)->orderBy('sort_order')->get();
    }

    private function syncCategories(ContentItem $item, array $categoryIds): void
    {
        $ids = collect($categoryIds)->push($this->sectionCategory()->id)->unique()->all();
        $item->categories()->sync($ids);
    }
}
