<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Media;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PdfController extends Controller
{
    /**
     * Stream a PDF document from private storage. Soft-deleted media rows
     * are excluded automatically by the model's default query scope. Media
     * IDs are sequential and this route has no auth, so we also require the
     * file to actually be attached to a published, non-trashed content item
     * — otherwise a visitor could enumerate /pdf/1, /pdf/2, ... and read
     * draft or unlinked documents before they're meant to be public.
     *
     * Most content types attach their PDF through the generic content_media
     * pivot (collection "attachment"), which contentItems() covers. Books
     * are the exception: their PDF is a dedicated books.pdf_media_id column,
     * never inserted into that pivot, so it needs its own published check.
     */
    public function show(Media $media): StreamedResponse
    {
        abort_unless($media->mime_type === 'application/pdf', 404);
        abort_unless(Storage::disk($media->disk)->exists($media->path), 404);

        $isPublished = $media->contentItems()->published()->exists()
            || Book::where('pdf_media_id', $media->id)->whereHas('contentItem', fn ($q) => $q->published())->exists();
        abort_unless($isPublished, 404);

        $filename = preg_replace('/[^\p{L}\p{N}\-_\. ]+/u', '', $media->original_name) ?: 'document.pdf';

        return Storage::disk($media->disk)->response($media->path, $filename, [
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ]);
    }
}
