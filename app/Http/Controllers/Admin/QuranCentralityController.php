<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreQuranCentralityItemRequest;
use App\Http\Requests\Admin\UpdateQuranCentralityItemRequest;
use App\Models\Category;
use App\Models\ContentItem;
use App\Models\Media;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class QuranCentralityController extends Controller
{
    private const SECTION_CATEGORY_SLUG = 'quran-centrality';

    private const TYPES = ['article', 'study', 'video', 'pdf', 'image'];

    private const SORTABLE = ['sort_order', 'title', 'created_at', 'published_at'];

    private ?Category $sectionCategory = null;

    public function index(Request $request): View
    {
        $this->authorize('permission', 'content.view');

        $sort = in_array($request->string('sort')->toString(), self::SORTABLE, true) ? $request->string('sort')->toString() : 'sort_order';
        $dir = $request->string('dir')->toString() === 'desc' ? 'desc' : 'asc';

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
                'sort_order' => $data['sort_order'] ?? 0,
                'published_at' => $this->resolvePublishedAt($data['status'], $data['published_at'] ?? null, null),
                'meta' => $this->buildMeta($data),
            ]);

            $this->syncCategories($item, $data['category_ids'] ?? []);
            $this->syncTags($item, $data['tags'] ?? '');
            $this->replaceMedia($item, $request->file('cover_image'), 'cover', $request->user()->id);
            $this->replaceMedia($item, $request->file('attachment'), 'attachment', $request->user()->id);

            return $item;
        });

        $this->record('quran_centrality.created', $item);

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
                'sort_order' => $data['sort_order'] ?? 0,
                'published_at' => $this->resolvePublishedAt($data['status'], $data['published_at'] ?? null, $item->published_at),
                'meta' => $this->buildMeta($data),
            ]);

            $this->syncCategories($item, $data['category_ids'] ?? []);
            $this->syncTags($item, $data['tags'] ?? '');
            $this->replaceMedia($item, $request->file('cover_image'), 'cover', $request->user()->id);
            $this->replaceMedia($item, $request->file('attachment'), 'attachment', $request->user()->id);
        });

        $this->record('quran_centrality.updated', $item);

        return redirect()->route('admin.quran-centrality.index')->with('status', 'تم تحديث المحتوى.');
    }

    public function destroy(ContentItem $item): RedirectResponse
    {
        $this->authorizeItem('delete', $item);
        $item->delete();
        $this->record('quran_centrality.deleted', $item);

        return redirect()->route('admin.quran-centrality.index')->with('status', 'تم نقل المحتوى إلى المحذوفات.');
    }

    public function restore(int $id): RedirectResponse
    {
        $this->authorize('permission', 'content.update');
        $item = ContentItem::onlyTrashed()->findOrFail($id);
        abort_unless($item->categories()->where('slug', self::SECTION_CATEGORY_SLUG)->exists(), 404);

        $item->restore();
        $this->record('quran_centrality.restored', $item);

        return redirect()->route('admin.quran-centrality.index', ['trashed' => 1])->with('status', 'تمت استعادة المحتوى.');
    }

    public function publish(ContentItem $item): RedirectResponse
    {
        $this->authorizeItem('update', $item);
        $item->update(['status' => 'published', 'published_at' => $item->published_at ?? now()]);
        $this->record('quran_centrality.published', $item);

        return back()->with('status', 'تم نشر المحتوى.');
    }

    public function unpublish(ContentItem $item): RedirectResponse
    {
        $this->authorizeItem('update', $item);
        $item->update(['status' => 'draft']);
        $this->record('quran_centrality.unpublished', $item);

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

    private function resolveSlug(?string $input, string $title, ?ContentItem $ignore = null): string
    {
        $base = $this->sanitizeSlug(filled($input) ? $input : $title);
        $slug = $base;
        $suffix = 2;

        while (ContentItem::withTrashed()->where('slug', $slug)->when($ignore, fn (Builder $q) => $q->whereKeyNot($ignore->id))->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    private function sanitizeSlug(string $value): string
    {
        $value = preg_replace('/[\s_]+/u', '-', trim($value));
        $value = preg_replace('/[^\p{L}\p{N}\-]+/u', '', $value);
        $value = trim($value, '-');

        return $value !== '' ? $value : (string) Str::uuid();
    }

    private function resolvePublishedAt(string $status, ?string $input, ?Carbon $existing): ?Carbon
    {
        if ($status !== 'published') {
            return null;
        }

        return filled($input) ? Carbon::parse($input) : ($existing ?? now());
    }

    private function buildMeta(array $data): array
    {
        return array_filter([
            'video_url' => $data['video_url'] ?? null,
            'seo' => array_filter([
                'title' => $data['seo_title'] ?? null,
                'description' => $data['seo_description'] ?? null,
                'keywords' => $data['seo_keywords'] ?? null,
            ]),
        ], fn ($value) => $value !== null && $value !== []);
    }

    private function syncCategories(ContentItem $item, array $categoryIds): void
    {
        $ids = collect($categoryIds)->push($this->sectionCategory()->id)->unique()->all();
        $item->categories()->sync($ids);
    }

    private function syncTags(ContentItem $item, string $tagsInput): void
    {
        $ids = collect(explode(',', $tagsInput))
            ->map(fn (string $name) => trim($name))
            ->filter()
            ->unique()
            ->map(fn (string $name) => Tag::firstOrCreate(['slug' => $this->sanitizeSlug($name)], ['name' => $name])->id);

        $item->tags()->sync($ids);
    }

    private function replaceMedia(ContentItem $item, ?UploadedFile $file, string $collection, int $userId): void
    {
        if (! $file) {
            return;
        }

        $oldIds = $item->media()->wherePivot('collection', $collection)->pluck('media.id');
        if ($oldIds->isNotEmpty()) {
            $item->media()->detach($oldIds->all());
        }

        $media = $this->createMedia($file, $userId);
        $item->media()->attach($media->id, ['collection' => $collection, 'sort_order' => 0]);
    }

    private function createMedia(UploadedFile $file, int $userId): Media
    {
        $path = $file->store('quran-centrality', 'public');
        [$width, $height] = @getimagesize($file->getRealPath()) ?: [null, null];

        return Media::create([
            'uploaded_by' => $userId,
            'disk' => 'public',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'width' => $width,
            'height' => $height,
        ]);
    }

    private function record(string $event, ContentItem $item): void
    {
        DB::table('activity_logs')->insert([
            'user_id' => auth()->id(),
            'event' => $event,
            'subject_type' => ContentItem::class,
            'subject_id' => $item->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'properties' => null,
            'created_at' => now(),
        ]);
    }
}
