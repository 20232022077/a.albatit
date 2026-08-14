<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Biography;
use App\Models\ContentItem;
use App\Models\Media;
use App\Support\ActivityLogger;
use App\Support\SafeFileUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BiographyController extends Controller
{
    public function edit()
    {
        $this->authorize('permission', 'content.update');
        $biography = Biography::with('contentItem', 'sections')->first();

        return view('admin.biography.edit', compact('biography'));
    }

    public function update(Request $request)
    {
        $this->authorize('permission', 'content.update');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string'],
            'body' => ['nullable', 'string'],
            'is_visible' => ['required', 'boolean'],
            'social_links' => ['nullable', 'string'],
            'profile_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'sections' => ['nullable', 'array'],
            'sections.*.id' => ['nullable', 'integer'],
            'sections.*.type' => ['required', 'in:qualification,work,development,teaching,achievement'],
            'sections.*.title' => ['required', 'string', 'max:255'],
            'sections.*.body' => ['nullable', 'string'],
            'sections.*.sort_order' => ['required', 'integer', 'min:0'],
            'sections.*.is_visible' => ['required', 'boolean'],
        ]);

        $socialLinks = [];
        if (filled($data['social_links'] ?? null)) {
            $decoded = json_decode($data['social_links'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $socialLinks = $decoded;
            }
        }

        $content = DB::transaction(function () use ($data, $request, $socialLinks) {
            $content = ContentItem::firstOrCreate(['type' => 'biography'], ['title' => $data['name'], 'slug' => 'biography', 'status' => 'draft']);
            $content->update([
                'title' => $data['name'],
                'excerpt' => $data['excerpt'],
                'body' => $data['body'],
                'status' => $data['is_visible'] ? 'published' : 'draft',
                'published_at' => $data['is_visible'] ? ($content->published_at ?? now()) : null,
                'meta' => ['social_links' => $socialLinks],
            ]);

            $bio = Biography::firstOrCreate(['content_item_id' => $content->id]);

            if ($request->hasFile('profile_image')) {
                $this->replaceProfileImage($bio, $request);
            }

            $ids = [];
            foreach ($data['sections'] ?? [] as $section) {
                $item = $bio->sections()->updateOrCreate(['id' => $section['id'] ?? null], $section);
                $ids[] = $item->id;
            }
            $bio->sections()->whereNotIn('id', $ids)->delete();

            return $content;
        });

        ActivityLogger::log('biography.updated', $content, ['name' => $data['name'], 'is_visible' => $data['is_visible']]);

        return back()->with('status', 'تم حفظ السيرة الذاتية.');
    }

    private function replaceProfileImage(Biography $bio, Request $request): void
    {
        $oldId = $bio->profile_media_id;
        $file = $request->file('profile_image');
        SafeFileUpload::assertSafe($file, SafeFileUpload::IMAGE_EXTENSIONS, 4096);

        $path = $file->store('biography', 'public');
        [$width, $height] = @getimagesize($file->getRealPath()) ?: [null, null];
        $variants = $width ? SafeFileUpload::generateImageVariants('public', $path) : [];

        $media = Media::create([
            'uploaded_by' => $request->user()->id,
            'disk' => 'public',
            'path' => $path,
            'original_name' => basename($file->getClientOriginalName()),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'width' => $width,
            'height' => $height,
            'metadata' => $variants !== [] ? ['variants' => $variants] : null,
        ]);

        $bio->update(['profile_media_id' => $media->id]);

        if ($oldId && $old = Media::find($oldId)) {
            Storage::disk($old->disk)->delete($old->path);
            SafeFileUpload::deleteVariants($old->disk, $old->metadata['variants'] ?? []);
            $old->delete();
        }
    }
}
