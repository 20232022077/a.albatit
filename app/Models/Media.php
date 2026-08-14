<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uploaded_by', 'disk', 'path', 'original_name', 'alt_text', 'caption', 'mime_type', 'size', 'checksum', 'width', 'height', 'metadata',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    /**
     * PDFs live on the private disk and are never linked to directly;
     * they are always served through the signed-free but MIME-checked
     * streaming route instead.
     */
    public function pdfUrl(): ?string
    {
        return $this->isPdf() ? route('pdf.show', $this) : null;
    }

    public function webpUrl(): ?string
    {
        $path = $this->metadata['variants']['webp'] ?? null;

        return $path ? Storage::disk($this->disk)->url($path) : null;
    }

    /**
     * The best URL to actually render this image with: the WebP variant
     * when one was generated (smaller, modern format), falling back to the
     * original file. Use this instead of url() anywhere an image is being
     * displayed; url() stays for the literal original (e.g. video/PDF
     * sources, or anywhere the exact uploaded file is required).
     */
    public function displayUrl(): string
    {
        return $this->webpUrl() ?? $this->url();
    }

    public function avifUrl(): ?string
    {
        $path = $this->metadata['variants']['avif'] ?? null;

        return $path ? Storage::disk($this->disk)->url($path) : null;
    }

    public function dimensions(): ?string
    {
        return $this->width && $this->height ? "{$this->width}×{$this->height}" : null;
    }

    public function humanSize(): string
    {
        $bytes = (float) $this->size;
        $units = ['بايت', 'كيلوبايت', 'ميجابايت', 'جيجابايت'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return ($i === 0 ? (string) $bytes : number_format($bytes, 1)).' '.$units[$i];
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function contentItems()
    {
        return $this->belongsToMany(ContentItem::class, 'content_media')->withPivot('collection', 'alt_text', 'sort_order')->withTimestamps();
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $q) use ($term) {
            $q->where('original_name', 'like', "%{$term}%")
                ->orWhere('alt_text', 'like', "%{$term}%")
                ->orWhere('caption', 'like', "%{$term}%");
        });
    }
}
