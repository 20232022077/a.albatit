<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ManagesContentItems;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBookRequest;
use App\Http\Requests\Admin\UpdateBookRequest;
use App\Models\Book;
use App\Models\Category;
use App\Models\ContentItem;
use App\Models\Media;
use App\Support\SafeFileUpload;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BookController extends Controller
{
    use ManagesContentItems;

    private const SORTABLE = ['sort_order', 'title', 'created_at', 'published_at'];

    public function index(Request $request): View
    {
        $this->authorize('permission', 'content.view');

        $sort = in_array($request->string('sort')->toString(), self::SORTABLE, true) ? $request->string('sort')->toString() : 'sort_order';
        $dir = $request->string('dir')->toString() === 'desc' ? 'desc' : 'asc';

        $items = ContentItem::query()
            ->ofType('book')
            ->with(['book.cover', 'categories'])
            ->when($request->boolean('trashed'), fn (Builder $q) => $q->onlyTrashed())
            ->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->string('status')->toString()))
            ->when($request->filled('category_id'), fn (Builder $q) => $q->whereHas(
                'categories', fn (Builder $c) => $c->where('categories.id', $request->integer('category_id'))
            ))
            ->search($request->string('q')->toString() ?: null)
            ->orderBy($sort, $dir)
            ->paginate(20)
            ->withQueryString();

        return view('admin.books.index', [
            'items' => $items,
            'categories' => Category::orderBy('name')->get(),
            'sort' => $sort,
            'dir' => $dir,
        ]);
    }

    public function create(): View
    {
        $this->authorize('permission', 'content.create');

        return view('admin.books.form', [
            'item' => new ContentItem(['status' => 'draft', 'sort_order' => 0]),
            'book' => new Book,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function store(StoreBookRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $item = DB::transaction(function () use ($data, $request) {
            $item = ContentItem::create([
                'author_id' => $request->user()->id,
                'type' => 'book',
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

            $book = Book::create([
                'content_item_id' => $item->id,
                'author_name' => $data['author_name'],
                'isbn' => $data['isbn'] ?? null,
                'publisher' => $data['publisher'] ?? null,
                'publication_year' => $data['publication_year'] ?? null,
                'pages_count' => $data['pages_count'] ?? null,
            ]);

            $this->syncCategories($item, $data['category_ids'] ?? []);
            $this->syncTags($item, $data['tags'] ?? '');
            $this->replaceBookFile($book, 'cover_media_id', $request->file('cover_image'), $request->user()->id);
            $this->replaceBookFile($book, 'pdf_media_id', $request->file('pdf_file'), $request->user()->id);

            return $item;
        });

        $this->recordActivity('books.created', $item);

        return redirect()->route('admin.books.index')->with('status', 'تم إنشاء الكتاب.');
    }

    public function edit(ContentItem $item): View
    {
        $this->authorizeBookItem($item);
        $item->load(['categories', 'tags']);

        return view('admin.books.form', [
            'item' => $item,
            'book' => $item->book ?? new Book,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateBookRequest $request, ContentItem $item): RedirectResponse
    {
        $this->authorizeBookItem($item);
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

            $book = $item->book ?? Book::create(['content_item_id' => $item->id]);
            $book->update([
                'author_name' => $data['author_name'],
                'isbn' => $data['isbn'] ?? null,
                'publisher' => $data['publisher'] ?? null,
                'publication_year' => $data['publication_year'] ?? null,
                'pages_count' => $data['pages_count'] ?? null,
            ]);

            $this->syncCategories($item, $data['category_ids'] ?? []);
            $this->syncTags($item, $data['tags'] ?? '');
            $this->replaceBookFile($book, 'cover_media_id', $request->file('cover_image'), $request->user()->id, $request->boolean('remove_cover_image'));
            $this->replaceBookFile($book, 'pdf_media_id', $request->file('pdf_file'), $request->user()->id);
        });

        $this->recordActivity('books.updated', $item);

        return redirect()->route('admin.books.index')->with('status', 'تم تحديث الكتاب.');
    }

    public function destroy(ContentItem $item): RedirectResponse
    {
        $this->authorizeBookItem($item, 'content.delete');
        $item->delete();
        $this->recordActivity('books.deleted', $item);

        return redirect()->route('admin.books.index')->with('status', 'تم نقل الكتاب إلى المحذوفات.');
    }

    public function restore(int $id): RedirectResponse
    {
        $this->authorize('permission', 'content.update');
        $item = ContentItem::onlyTrashed()->where('type', 'book')->findOrFail($id);
        $item->restore();
        $this->recordActivity('books.restored', $item);

        return redirect()->route('admin.books.index', ['trashed' => 1])->with('status', 'تمت استعادة الكتاب.');
    }

    public function publish(ContentItem $item): RedirectResponse
    {
        $this->authorizeBookItem($item);
        $item->update(['status' => 'published', 'published_at' => $item->published_at ?? now()]);
        $this->recordActivity('books.published', $item);

        return back()->with('status', 'تم نشر الكتاب.');
    }

    public function unpublish(ContentItem $item): RedirectResponse
    {
        $this->authorizeBookItem($item);
        $item->update(['status' => 'unpublished']);
        $this->recordActivity('books.unpublished', $item);

        return back()->with('status', 'تم إلغاء نشر الكتاب.');
    }

    private function authorizeBookItem(ContentItem $item, string $permission = 'content.update'): void
    {
        $this->authorize('permission', $permission);
        abort_unless($item->type === 'book', 404);
    }

    private function replaceBookFile(Book $book, string $column, ?UploadedFile $file, int $userId, bool $remove = false): void
    {
        if (! $file && ! $remove) {
            return;
        }

        $oldId = $book->{$column};

        if ($file) {
            $media = $this->createMedia($file, 'books', $userId);
            $book->update([$column => $media->id]);
        } else {
            $book->update([$column => null]);
        }

        if ($oldId && $old = Media::find($oldId)) {
            Storage::disk($old->disk)->delete($old->path);
            SafeFileUpload::deleteVariants($old->disk, $old->metadata['variants'] ?? []);
            $old->delete();
        }
    }
}
