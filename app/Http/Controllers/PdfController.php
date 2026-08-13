<?php

namespace App\Http\Controllers;

use App\Models\Media;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PdfController extends Controller
{
    /**
     * Stream a PDF document from private storage. Soft-deleted media rows
     * are excluded automatically by the model's default query scope, so a
     * removed document 404s here without any extra checks.
     */
    public function show(Media $media): StreamedResponse
    {
        abort_unless($media->mime_type === 'application/pdf', 404);
        abort_unless(Storage::disk($media->disk)->exists($media->path), 404);

        $filename = preg_replace('/[^\p{L}\p{N}\-_\. ]+/u', '', $media->original_name) ?: 'document.pdf';

        return Storage::disk($media->disk)->response($media->path, $filename, [
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ]);
    }
}
