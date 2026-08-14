<?php

namespace App\Http\Controllers;

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
     */
    public function show(Media $media): StreamedResponse
    {
        abort_unless($media->mime_type === 'application/pdf', 404);
        abort_unless(Storage::disk($media->disk)->exists($media->path), 404);
        abort_unless($media->contentItems()->published()->exists(), 404);

        $filename = preg_replace('/[^\p{L}\p{N}\-_\. ]+/u', '', $media->original_name) ?: 'document.pdf';

        return Storage::disk($media->disk)->response($media->path, $filename, [
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ]);
    }
}
