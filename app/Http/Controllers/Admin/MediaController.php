<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMediaRequest;
use App\Http\Requests\Admin\UpdateMediaRequest;
use App\Models\Media;
use App\Support\ActivityLogger;
use App\Support\SafeFileUpload;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MediaController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('permission', 'content.view');

        $type = $request->string('type')->toString();

        $items = Media::query()
            ->withCount('contentItems')
            ->when($request->boolean('trashed'), fn (Builder $q) => $q->onlyTrashed())
            ->when($type === 'pdf', fn (Builder $q) => $q->where('mime_type', 'application/pdf'))
            ->when($type === 'image', fn (Builder $q) => $q->where('mime_type', 'like', 'image/%'))
            ->when($request->filled('q'), fn (Builder $q) => $q->search($request->string('q')->toString()))
            ->latest()
            ->paginate(24)
            ->withQueryString();

        return view('admin.media.index', ['items' => $items]);
    }

    public function store(StoreMediaRequest $request): RedirectResponse
    {
        $files = $request->file('files', []);

        // Validate every file before storing any of them. A DB transaction
        // around the loop below wouldn't help here — the abort on a later
        // file happens after earlier files are already written to disk, and
        // rolling back the DB inserts wouldn't clean those files up. Failing
        // this pass first means a rejected file can't leave an earlier,
        // valid file's upload half-committed.
        foreach ($files as $file) {
            $extension = strtolower($file->getClientOriginalExtension());
            $type = SafeFileUpload::classify($extension);
            abort_unless(in_array($type, ['image', 'pdf'], true), 422, 'نوع الملف غير مدعوم في مكتبة الوسائط.');

            $allowed = $type === 'pdf' ? SafeFileUpload::PDF_EXTENSIONS : SafeFileUpload::IMAGE_EXTENSIONS;
            $maxKb = $type === 'pdf' ? 51200 : 10240;
            SafeFileUpload::assertSafe($file, $allowed, $maxKb);
        }

        $created = 0;

        foreach ($files as $file) {
            $extension = strtolower($file->getClientOriginalExtension());
            $type = SafeFileUpload::classify($extension);
            $disk = SafeFileUpload::diskFor($type);
            $directory = ($type === 'pdf' ? 'pdfs/' : '').'media/'.now()->format('Y/m');
            $path = $file->store($directory, $disk);

            $dimensions = $type === 'image' ? (@getimagesize($file->getRealPath()) ?: [null, null]) : [null, null];
            [$width, $height] = $dimensions;
            $variants = $width ? SafeFileUpload::generateImageVariants($disk, $path) : [];

            $media = Media::create([
                'uploaded_by' => $request->user()->id,
                'disk' => $disk,
                'path' => $path,
                'original_name' => basename($file->getClientOriginalName()),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'width' => $width,
                'height' => $height,
                'metadata' => $variants !== [] ? ['variants' => $variants] : null,
            ]);

            $this->record('media.uploaded', $media);
            $created++;
        }

        return back()->with('status', "تم رفع {$created} ملف بنجاح.");
    }

    public function update(UpdateMediaRequest $request, Media $media): RedirectResponse
    {
        $media->update($request->validated());
        $this->record('media.updated', $media);

        return back()->with('status', 'تم تحديث بيانات الصورة.');
    }

    public function destroy(Media $media): RedirectResponse
    {
        $this->authorize('permission', 'content.delete');
        $media->delete();
        $this->record('media.deleted', $media);

        return redirect()->route('admin.media.index')->with('status', 'تم نقل الصورة إلى المحذوفات.');
    }

    public function restore(int $id): RedirectResponse
    {
        $this->authorize('permission', 'content.update');
        $media = Media::onlyTrashed()->findOrFail($id);
        $media->restore();
        $this->record('media.restored', $media);

        return redirect()->route('admin.media.index', ['trashed' => 1])->with('status', 'تمت استعادة الصورة.');
    }

    public function forceDestroy(int $id): RedirectResponse
    {
        $this->authorize('permission', 'content.delete');
        $media = Media::onlyTrashed()->findOrFail($id);

        if ($media->contentItems()->exists()) {
            return back()->with('error', 'لا يمكن حذف هذه الصورة نهائيًا لأنها مستخدمة في محتوى آخر.');
        }

        Storage::disk($media->disk)->delete($media->path);
        SafeFileUpload::deleteVariants($media->disk, $media->metadata['variants'] ?? []);
        $this->record('media.force_deleted', $media);
        $media->forceDelete();

        return redirect()->route('admin.media.index', ['trashed' => 1])->with('status', 'تم حذف الصورة نهائيًا.');
    }

    private function record(string $event, Media $media): void
    {
        ActivityLogger::log($event, $media, ['original_name' => $media->original_name]);
    }
}
