<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicStorageController extends Controller
{
    /**
     * Fallback for serving the "public" disk when the web server can't
     * serve /storage directly (e.g. hosting that doesn't support the
     * symlink `php artisan storage:link` normally creates). Apache's
     * rewrite rule only reaches this route when no real static file
     * already exists at that path, so on hosts where the symlink does
     * work, this never runs — the web server keeps serving those files
     * directly. Always scoped to the "public" disk, never the private
     * one PDFs live on, regardless of the requested path.
     */
    public function show(string $path): StreamedResponse
    {
        abort_if(str_contains($path, '..'), 404);
        abort_unless(Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path);
    }
}
