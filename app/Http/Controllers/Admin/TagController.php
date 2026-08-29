<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTagRequest;
use App\Http\Requests\Admin\UpdateTagRequest;
use App\Models\Tag;
use App\Support\ActivityLogger;
use App\Support\Slug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TagController extends Controller
{
    private const SORTABLE = ['name', 'created_at'];

    public function index(Request $request): View
    {
        $this->authorize('permission', 'content.view');

        $sort = in_array($request->string('sort')->toString(), self::SORTABLE, true) ? $request->string('sort')->toString() : 'name';
        $dir = $request->string('dir')->toString() === 'desc' ? 'desc' : 'asc';

        $items = Tag::query()
            ->withCount('contentItems')
            ->when($request->boolean('trashed'), fn (Builder $q) => $q->onlyTrashed())
            ->when($request->filled('status'), fn (Builder $q) => $q->where('is_active', $request->string('status')->toString() === 'active'))
            ->when($request->filled('q'), fn (Builder $q) => $q->where('name', 'like', '%'.$request->string('q').'%'))
            ->orderBy($sort, $dir)
            ->paginate(20)
            ->withQueryString();

        return view('admin.tags.index', [
            'items' => $items,
            'sort' => $sort,
            'dir' => $dir,
        ]);
    }

    public function create(): View
    {
        $this->authorize('permission', 'content.create');

        return view('admin.tags.form', ['tag' => new Tag(['is_active' => true])]);
    }

    public function store(StoreTagRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $tag = Tag::create([
            'name' => $data['name'],
            'slug' => $this->resolveSlug($data['slug'] ?? null, $data['name']),
            'is_active' => $request->boolean('is_active'),
        ]);

        $this->record('tags.created', $tag);

        return redirect()->route('admin.tags.index')->with('status', 'تم إنشاء الوسم.');
    }

    public function edit(Tag $tag): View
    {
        $this->authorize('permission', 'content.update');

        return view('admin.tags.form', compact('tag'));
    }

    public function update(UpdateTagRequest $request, Tag $tag): RedirectResponse
    {
        $data = $request->validated();

        $tag->update([
            'name' => $data['name'],
            'slug' => $this->resolveSlug($data['slug'] ?? null, $data['name'], $tag),
            'is_active' => $request->boolean('is_active'),
        ]);

        $this->record('tags.updated', $tag);

        return redirect()->route('admin.tags.index')->with('status', 'تم تحديث الوسم.');
    }

    public function destroy(Tag $tag): RedirectResponse
    {
        $this->authorize('permission', 'content.delete');
        $tag->delete();
        $this->record('tags.deleted', $tag);

        return redirect()->route('admin.tags.index')->with('status', 'تم نقل الوسم إلى المحذوفات.');
    }

    public function restore(int $id): RedirectResponse
    {
        $this->authorize('permission', 'content.update');
        $tag = Tag::onlyTrashed()->findOrFail($id);
        $tag->restore();
        $this->record('tags.restored', $tag);

        return redirect()->route('admin.tags.index', ['trashed' => 1])->with('status', 'تمت استعادة الوسم.');
    }

    public function activate(Tag $tag): RedirectResponse
    {
        $this->authorize('permission', 'content.update');
        $tag->update(['is_active' => true]);
        $this->record('tags.activated', $tag);

        return back()->with('status', 'تم تفعيل الوسم.');
    }

    public function deactivate(Tag $tag): RedirectResponse
    {
        $this->authorize('permission', 'content.update');
        $tag->update(['is_active' => false]);
        $this->record('tags.deactivated', $tag);

        return back()->with('status', 'تم تعطيل الوسم.');
    }

    private function resolveSlug(?string $input, string $name, ?Tag $ignore = null): string
    {
        $base = Slug::sanitize(filled($input) ? $input : $name);
        $slug = $base;
        $suffix = 2;

        while (Tag::withTrashed()->where('slug', $slug)->when($ignore, fn (Builder $q) => $q->whereKeyNot($ignore->id))->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    private function record(string $event, Tag $tag): void
    {
        ActivityLogger::log($event, $tag, ['name' => $tag->name]);
    }
}
