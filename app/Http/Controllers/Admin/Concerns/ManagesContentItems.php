<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Models\ContentItem;
use App\Models\Media;
use App\Models\Tag;
use App\Support\SafeFileUpload;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait ManagesContentItems
{
    protected function resolveSlug(?string $input, string $title, ?ContentItem $ignore = null): string
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

    protected function sanitizeSlug(string $value): string
    {
        $value = preg_replace('/[\s_]+/u', '-', trim($value));
        $value = preg_replace('/[^\p{L}\p{N}\-]+/u', '', $value);
        $value = trim($value, '-');

        return $value !== '' ? $value : (string) Str::uuid();
    }

    protected function resolvePublishedAt(string $status, ?string $input, ?Carbon $existing): ?Carbon
    {
        if ($status !== 'published') {
            return null;
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
