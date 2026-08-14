<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Models\ContentItem;
use App\Models\ContentItemSlug;
use App\Models\Media;
use App\Models\Tag;
use App\Support\ActivityLogger;
use App\Support\SafeFileUpload;
use App\Support\SafeYoutube;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait ManagesContentItems
{
    protected function resolveSlug(?string $input, string $title, ?ContentItem $ignore = null): string
    {
        $base = $this->sanitizeSlug(filled($input) ? $input : $title);
        $slug = $base;
        $suffix = 2;

        $collides = function (string $candidate) use ($ignore) {
            $inUse = ContentItem::withTrashed()->where('slug', $candidate)
                ->when($ignore, fn (Builder $q) => $q->whereKeyNot($ignore->id))
                ->exists();

            $wasUsed = ContentItemSlug::where('slug', $candidate)
                ->when($ignore, fn (Builder $q) => $q->where('content_item_id', '!=', $ignore->id))
                ->exists();

            return $inUse || $wasUsed;
        };

        while ($collides($slug)) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        if ($ignore && filled($ignore->slug) && $ignore->slug !== $slug) {
            ContentItemSlug::firstOrCreate(['content_item_id' => $ignore->id, 'slug' => $ignore->slug]);
        }

        return $slug;
    }

    protected function sanitizeSlug(string $value): string
    {
        $value = preg_replace('/[\s_]+/u', '-', trim($value));
        $value = preg_replace('/[^\p{L}\p{N}\-]+/u', '', $value);
        $value = trim($value, '-');

        return $value !== '' ? $value : (string) Str::uuid();
    }

    protected function resolvePublishedAt(string $status, ?string $input, ?Carbon $existing): ?Carbon
    {
        if ($status === 'draft') {
            return null;
        }

        if ($status === 'unpublished') {
            return $existing;
        }

        return filled($input) ? Carbon::parse($input) : ($existing ?? now());
    }

    protected function buildSeoMeta(array $data, array $extra = []): array
    {
        return array_filter([
            ...$extra,
            'seo' => array_filter([
                'title' => $data['seo_title'] ?? null,
                'description' => $data['seo_description'] ?? null,
                'keywords' => $data['seo_keywords'] ?? null,
            ]),
        ], fn ($value) => $value !== null && $value !== []);
    }

    /**
     * The FormRequest already rejected any video_url that doesn't resolve to
     * a valid YouTube video ID, so this just re-derives that same ID for
     * storage. The stored video_id (not the raw URL) is what every embed is
     * built from — the URL is kept only for prefilling the admin form.
     */
    protected function resolveYoutubeMeta(?string $url): array
    {
        if (blank($url)) {
            return [];
        }

        $videoId = SafeYoutube::extractVideoId($url);

        return $videoId ? ['video_url' => $url, 'video_id' => $videoId] : [];
    }

    protected function syncTags(ContentItem $item, string $tagsInput): void
    {
        $ids = collect(explode(',', $tagsInput))
            ->map(fn (string $name) => trim($name))
            ->filter()
            ->unique()
            ->map(fn (string $name) => Tag::firstOrCreate(['slug' => $this->sanitizeSlug($name)], ['name' => $name])->id);

        $item->tags()->sync($ids);
    }

    protected function createMedia(UploadedFile $file, string $directory, int $userId): Media
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $type = SafeFileUpload::classify($extension);

        $allowed = match ($type) {
            'pdf' => SafeFileUpload::PDF_EXTENSIONS,
            'video' => SafeFileUpload::VIDEO_EXTENSIONS,
            default => SafeFileUpload::IMAGE_EXTENSIONS,
        };
        $maxKb = match ($type) {
            'pdf', 'video' => 51200,
            default => 10240,
        };
        SafeFileUpload::assertSafe($file, $allowed, $maxKb);

        $disk = SafeFileUpload::diskFor($type ?? 'image');
        $storeDirectory = $type === 'pdf' ? 'pdfs/'.$directory : $directory;
        $path = $file->store($storeDirectory, $disk);

        $dimensions = $type === 'image' ? (@getimagesize($file->getRealPath()) ?: [null, null]) : [null, null];
        [$width, $height] = $dimensions;
        $variants = $width ? SafeFileUpload::generateImageVariants($disk, $path) : [];

        return Media::create([
            'uploaded_by' => $userId,
            'disk' => $disk,
            'path' => $path,
            'original_name' => basename($file->getClientOriginalName()),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'width' => $width,
            'height' => $height,
            'metadata' => $variants !== [] ? ['variants' => $variants] : null,
        ]);
    }

    protected function replaceMediaCollection(ContentItem $item, ?UploadedFile $file, string $collection, string $directory, int $userId): void
    {
        if (! $file) {
            return;
        }

        $old = $item->media()->wherePivot('collection', $collection)->get();
        if ($old->isNotEmpty()) {
            $item->media()->detach($old->pluck('id')->all());
            foreach ($old as $oldMedia) {
                Storage::disk($oldMedia->disk)->delete($oldMedia->path);
                SafeFileUpload::deleteVariants($oldMedia->disk, $oldMedia->metadata['variants'] ?? []);
                $oldMedia->delete();
            }
        }

        $media = $this->createMedia($file, $directory, $userId);
        $item->media()->attach($media->id, ['collection' => $collection, 'sort_order' => 0]);
    }

    protected function recordActivity(string $event, ContentItem $item): void
    {
        ActivityLogger::log($event, $item, array_filter(['title' => $item->title, 'type' => $item->type, 'status' => $item->status]));
    }
}
