<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\Category;
use App\Models\Media;
use App\Support\ActivityLogger;
use App\Support\SafeFileUpload;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    private const SORTABLE = ['name', 'sort_order', 'created_at'];

    public function index(Request $request): View
    {
        $this->authorize('permission', 'content.view');

        $sort = in_array($request->string('sort')->toString(), self::SORTABLE, true) ? $request->string('sort')->toString() : 'sort_order';
        $dir = $request->string('dir')->toString() === 'desc' ? 'desc' : 'asc';

        $items = Category::query()
            ->with('parent')
            ->when($request->boolean('trashed'), fn (Builder $q) => $q->onlyTrashed())
            ->when($request->filled('status'), fn (Builder $q) => $q->where('is_active', $request->string('status')->toString() === 'active'))
            ->when($request->filled('q'), fn (Builder $q) => $q->where(
                fn (Builder $inner) => $inner->where('name', 'like', '%'.$request->string('q').'%')
                    ->orWhere('description', 'like', '%'.$request->string('q').'%')
            ))
            ->orderBy($sort, $dir)
            ->paginate(20)
            ->withQueryString();

        return view('admin.categories.index', [
            'items' => $items,
            'sort' => $sort,
            'dir' => $dir,
        ]);
    }

    public function create(): View
    {
        $this->authorize('permission', 'content.create');

        return view('admin.categories.form', [
            'category' => new Category(['is_active' => true, 'sort_order' => 0]),
            'parents' => Category::orderBy('name')->get(),
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $category = DB::transaction(function () use ($data, $request) {
            $category = Category::create([
                'parent_id' => $data['parent_id'] ?? null,
                'name' => $data['name'],
                'slug' => $this->resolveSlug($data['slug'] ?? null, $data['name']),
                'description' => $data['description'] ?? null,
                'sort_order' => $data['sort_order'] ?? 0,
                'is_active' => $request->boolean('is_active'),
                'meta' => $this->buildSeoMeta($data),
            ]);

            $this->replaceImage($category, $request->file('image'), $request->user()->id);

            return $category;
        });

        $this->record('categories.created', $category);
        Category::flushCache();

        return redirect()->route('admin.categories.index')->with('status', 'تم إنشاء التصنيف.');
    }

    public function edit(Category $category): View
    {
        $this->authorize('permission', 'content.update');

        return view('admin.categories.form', [
            'category' => $category,
            'parents' => Category::where('id', '!=', $category->id)->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $request, $category) {
            $category->update([
                'parent_id' => $data['parent_id'] ?? null,
                'name' => $data['name'],
                'slug' => $this->resolveSlug($data['slug'] ?? null, $data['name'], $category),
                'description' => $data['description'] ?? null,
                'sort_order' => $data['sort_order'] ?? 0,
                'is_active' => $request->boolean('is_active'),
                'meta' => $this->buildSeoMeta($data),
            ]);

            $this->replaceImage($category, $request->file('image'), $request->user()->id);
        });

        $this->record('categories.updated', $category);
        Category::flushCache();

        return redirect()->route('admin.categories.index')->with('status', 'تم تحديث التصنيف.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->authorize('permission', 'content.delete');
        $category->delete();
        $this->record('categories.deleted', $category);
        Category::flushCache();

        return redirect()->route('admin.categories.index')->with('status', 'تم نقل التصنيف إلى المحذوفات.');
    }

    public function restore(int $id): RedirectResponse
    {
        $this->authorize('permission', 'content.update');
        $category = Category::onlyTrashed()->findOrFail($id);
        $category->restore();
        $this->record('categories.restored', $category);
        Category::flushCache();

        return redirect()->route('admin.categories.index', ['trashed' => 1])->with('status', 'تمت استعادة التصنيف.');
    }

    public function activate(Category $category): RedirectResponse
    {
        $this->authorize('permission', 'content.update');
        $category->update(['is_active' => true]);
        $this->record('categories.activated', $category);
        Category::flushCache();

        return back()->with('status', 'تم تفعيل التصنيف.');
    }

    public function deactivate(Category $category): RedirectResponse
    {
        $this->authorize('permission', 'content.update');
        $category->update(['is_active' => false]);
        $this->record('categories.deactivated', $category);
        Category::flushCache();

        return back()->with('status', 'تم تعطيل التصنيف.');
    }

    private function resolveSlug(?string $input, string $name, ?Category $ignore = null): string
    {
        $base = $this->sanitizeSlug(filled($input) ? $input : $name);
        $slug = $base;
        $suffix = 2;

        while (Category::withTrashed()->where('slug', $slug)->when($ignore, fn (Builder $q) => $q->whereKeyNot($ignore->id))->exists()) {
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

    private function buildSeoMeta(array $data): array
    {
        return array_filter([
            'seo' => array_filter([
                'title' => $data['seo_title'] ?? null,
                'description' => $data['seo_description'] ?? null,
                'keywords' => $data['seo_keywords'] ?? null,
            ]),
        ], fn ($value) => $value !== null && $value !== []);
    }

    private function replaceImage(Category $category, ?UploadedFile $file, int $userId): void
    {
        if (! $file) {
            return;
        }

        SafeFileUpload::assertSafe($file, SafeFileUpload::IMAGE_EXTENSIONS, 4096);

        $oldId = $category->image_media_id;
        $path = $file->store('categories', 'public');
        [$width, $height] = @getimagesize($file->getRealPath()) ?: [null, null];
        $variants = $width ? SafeFileUpload::generateImageVariants('public', $path) : [];

        $media = Media::create([
            'uploaded_by' => $userId,
            'disk' => 'public',
            'path' => $path,
            'original_name' => basename($file->getClientOriginalName()),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'width' => $width,
            'height' => $height,
            'metadata' => $variants !== [] ? ['variants' => $variants] : null,
        ]);

        $category->update(['image_media_id' => $media->id]);

        if ($oldId && $old = Media::find($oldId)) {
            Storage::disk($old->disk)->delete($old->path);
            SafeFileUpload::deleteVariants($old->disk, $old->metadata['variants'] ?? []);
            $old->delete();
        }
    }

    private function record(string $event, Category $category): void
    {
        ActivityLogger::log($event, $category, ['name' => $category->name]);
    }
}
