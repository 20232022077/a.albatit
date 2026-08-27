<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ManagesContentItems;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProgramRequest;
use App\Http\Requests\Admin\UpdateProgramRequest;
use App\Models\Category;
use App\Models\ContentItem;
use App\Models\Program;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProgramController extends Controller
{
    use ManagesContentItems;

    private const SORTABLE = ['sort_order', 'title', 'created_at', 'published_at'];

    public function index(Request $request): View
    {
        $this->authorize('permission', 'content.view');

        $sort = in_array($request->string('sort')->toString(), self::SORTABLE, true) ? $request->string('sort')->toString() : 'sort_order';
        $dir = $request->string('dir')->toString() === 'desc' ? 'desc' : 'asc';

        $items = ContentItem::query()
            ->ofType('program')
            ->with(['categories', 'media', 'program.episodes'])
            ->when($request->boolean('trashed'), fn (Builder $q) => $q->onlyTrashed())
            ->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->string('status')->toString()))
            ->when($request->filled('category_id'), fn (Builder $q) => $q->whereHas(
                'categories', fn (Builder $c) => $c->where('categories.id', $request->integer('category_id'))
            ))
            ->search($request->string('q')->toString() ?: null)
            ->orderBy($sort, $dir)
            ->paginate(20)
            ->withQueryString();

        return view('admin.programs.index', [
            'items' => $items,
            'categories' => Category::orderBy('name')->get(),
            'sort' => $sort,
            'dir' => $dir,
        ]);
    }

    public function create(): View
    {
        $this->authorize('permission', 'content.create');

        return view('admin.programs.form', [
            'item' => new ContentItem(['status' => 'draft', 'sort_order' => 0]),
            'program' => new Program,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function store(StoreProgramRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $item = DB::transaction(function () use ($data, $request) {
            $item = ContentItem::create([
                'author_id' => $request->user()->id,
                'type' => 'program',
                'title' => $data['title'],
                'slug' => $this->resolveSlug($data['slug'] ?? null, $data['title']),
                'excerpt' => $data['excerpt'] ?? null,
                'body' => $data['body'] ?? null,
                'status' => $data['status'],
                'is_featured' => $request->boolean('is_featured'),
                'sort_order' => $data['sort_order'] ?? 0,
                'published_at' => $this->resolvePublishedAt($data['status'], $data['published_at'] ?? null, null),
                'meta' => $this->buildSeoMeta($data),
            ]);

            Program::create([
                'content_item_id' => $item->id,
                'presenter' => $data['presenter'] ?? null,
                'started_on' => $data['started_on'] ?? null,
                'ended_on' => $data['ended_on'] ?? null,
            ]);

            $this->syncCategories($item, $data['category_ids'] ?? []);
            $this->syncTags($item, $data['tags'] ?? '');
            $this->replaceMediaCollection($item, $request->file('cover_image'), 'cover', 'programs', $request->user()->id);

            return $item;
        });

        $this->recordActivity('programs.created', $item);

        return redirect()->route('admin.programs.index')->with('status', 'تم إنشاء البرنامج.');
    }

    public function edit(ContentItem $item): View
    {
        $this->authorizeProgramItem($item);
        $item->load(['categories', 'tags', 'media', 'program.episodes.contentItem']);

        return view('admin.programs.form', [
            'item' => $item,
            'program' => $item->program ?? new Program,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateProgramRequest $request, ContentItem $item): RedirectResponse
    {
        $this->authorizeProgramItem($item);
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
                'meta' => $this->buildSeoMeta($data),
            ]);

            $program = $item->program ?? Program::create(['content_item_id' => $item->id]);
            $program->update([
                'presenter' => $data['presenter'] ?? null,
                'started_on' => $data['started_on'] ?? null,
                'ended_on' => $data['ended_on'] ?? null,
            ]);

            $this->syncCategories($item, $data['category_ids'] ?? []);
            $this->syncTags($item, $data['tags'] ?? '');
            $this->replaceMediaCollection($item, $request->file('cover_image'), 'cover', 'programs', $request->user()->id, $request->boolean('remove_cover_image'));
        });

        $this->recordActivity('programs.updated', $item);

        return redirect()->route('admin.programs.index')->with('status', 'تم تحديث البرنامج.');
    }

    public function destroy(ContentItem $item): RedirectResponse
    {
        $this->authorizeProgramItem($item, 'content.delete');

        DB::transaction(function () use ($item) {
            $episodeIds = $item->program?->episodes->pluck('content_item_id') ?? collect();
            ContentItem::whereIn('id', $episodeIds)->get()->each->delete();
            $item->delete();
        });

        $this->recordActivity('programs.deleted', $item);

        return redirect()->route('admin.programs.index')->with('status', 'تم نقل البرنامج وحلقاته إلى المحذوفات.');
    }

    public function restore(int $id): RedirectResponse
    {
        $this->authorize('permission', 'content.update');
        $item = ContentItem::onlyTrashed()->where('type', 'program')->findOrFail($id);

        DB::transaction(function () use ($item) {
            $item->restore();
            $episodeIds = $item->program?->episodes->pluck('content_item_id') ?? collect();
            ContentItem::onlyTrashed()->whereIn('id', $episodeIds)->get()->each->restore();
        });

        $this->recordActivity('programs.restored', $item);

        return redirect()->route('admin.programs.index', ['trashed' => 1])->with('status', 'تمت استعادة البرنامج وحلقاته.');
    }

    public function publish(ContentItem $item): RedirectResponse
    {
        $this->authorizeProgramItem($item);
        $item->update(['status' => 'published', 'published_at' => $item->published_at ?? now()]);
        $this->recordActivity('programs.published', $item);

        return back()->with('status', 'تم نشر البرنامج.');
    }

    public function unpublish(ContentItem $item): RedirectResponse
    {
        $this->authorizeProgramItem($item);
        $item->update(['status' => 'unpublished']);
        $this->recordActivity('programs.unpublished', $item);

        return back()->with('status', 'تم إلغاء نشر البرنامج.');
    }

    private function authorizeProgramItem(ContentItem $item, string $permission = 'content.update'): void
    {
        $this->authorize('permission', $permission);
        abort_unless($item->type === 'program', 404);
    }

}
