<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SafeFileUpload
{
    public const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'avif'];

    public const VIDEO_EXTENSIONS = ['mp4', 'webm', 'mov'];

    public const PDF_EXTENSIONS = ['pdf'];

    /**
     * Size caps shared between Form Request validation rules (Laravel's
     * 'max:<kb>' rule) and the maxKb argument passed into assertSafe()
     * below, so the two never drift apart.
     */
    public const MAX_IMAGE_KB = 4096;

    /**
     * Ceiling used by ManagesContentItems::createMedia() for anything that
     * isn't a PDF/video — deliberately looser than MAX_IMAGE_KB because it
     * also covers the general media-library uploader (StoreMediaRequest),
     * which already allows files up to MAX_DOCUMENT_KB at the Form Request
     * layer before this second check runs.
     */
    public const MAX_MEDIA_LIBRARY_IMAGE_KB = 10240;

    public const MAX_DOCUMENT_KB = 51200;

    public const MAX_LOGO_KB = 2048;

    public const MAX_FAVICON_KB = 512;

    private const MIME_BY_EXTENSION = [
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'gif' => ['image/gif'],
        'webp' => ['image/webp'],
        'bmp' => ['image/bmp', 'image/x-ms-bmp'],
        'avif' => ['image/avif'],
        'pdf' => ['application/pdf'],
        'mp4' => ['video/mp4'],
        'webm' => ['video/webm'],
        'mov' => ['video/quicktime'],
    ];

    private const DANGEROUS_NAME_PATTERN = '/\.(php\d?|phtml|phar|pht|exe|sh|bat|cmd|cgi|js|jsx|mjs|htm|html|svg|asp|aspx|jsp)(\.|$)/i';

    /**
     * PDF "auto-action" tokens that can make a PDF behave like executable
     * code when opened (launch external programs, run embedded scripts).
     * Any match is treated as unsafe.
     */
    private const PDF_DANGEROUS_TOKENS = ['/JavaScript', '/JS', '/OpenAction', '/Launch', '/EmbeddedFile', '/RichMedia'];

    /**
     * Classify a file extension into a broad safety/storage category.
     */
    public static function classify(string $extension): ?string
    {
        $extension = strtolower($extension);

        return match (true) {
            in_array($extension, self::IMAGE_EXTENSIONS, true) => 'image',
            in_array($extension, self::PDF_EXTENSIONS, true) => 'pdf',
            in_array($extension, self::VIDEO_EXTENSIONS, true) => 'video',
            default => null,
        };
    }

    /**
     * PDFs are stored on the private, non-web-accessible disk since they
     * must never be reachable by a direct/guessable public URL; everything
     * else keeps using the public disk.
     */
    public static function diskFor(string $type): string
    {
        return $type === 'pdf' ? 'local' : 'public';
    }

    /**
     * Validate an uploaded file against its declared extension, its real
     * (content-sniffed) MIME type, its filename, and its actual decoded
     * content. Throws a ValidationException if anything looks unsafe.
     */
    public static function assertSafe(UploadedFile $file, array $allowedExtensions, ?int $maxKb = null): void
    {
        $errors = [];
        $originalName = $file->getClientOriginalName();

        if ($maxKb !== null && $file->getSize() > $maxKb * 1024) {
            $errors[] = 'حجم الملف يتجاوز الحد المسموح به.';
        }

        if (str_contains($originalName, "\0") || preg_match('/[\x00-\x1f]/', $originalName)) {
            $errors[] = 'اسم الملف يحتوي على رموز غير صالحة.';
        }

        if (preg_match(self::DANGEROUS_NAME_PATTERN, $originalName)) {
            $errors[] = 'اسم الملف أو امتداده غير مسموح به لأسباب أمنية.';
        }

        $extension = strtolower($file->getClientOriginalExtension());
        if (! in_array($extension, $allowedExtensions, true)) {
            $errors[] = 'امتداد الملف غير مسموح به.';
        }

        $realMime = @mime_content_type($file->getRealPath()) ?: null;
        $expectedMimes = self::MIME_BY_EXTENSION[$extension] ?? [];
        if (! $realMime || ($expectedMimes !== [] && ! in_array($realMime, $expectedMimes, true))) {
            $errors[] = 'نوع الملف الحقيقي المكتشف من المحتوى لا يطابق الامتداد.';
        }

        if (in_array($extension, self::IMAGE_EXTENSIONS, true)) {
            if (@getimagesize($file->getRealPath()) === false) {
                $errors[] = 'محتوى الملف ليس صورة صالحة.';
            }
        } elseif ($extension === 'pdf') {
            $header = '';
            if ($handle = @fopen($file->getRealPath(), 'rb')) {
                $header = fread($handle, 5);
                fclose($handle);
            }
            if ($header !== '%PDF-') {
                $errors[] = 'محتوى الملف ليس PDF صالحًا.';
            }

            $contents = @file_get_contents($file->getRealPath());
            if ($contents !== false) {
                // Require a valid PDF delimiter/whitespace right after the token so a
                // real `/JS(...)`, `/JS<<...>>`, etc. directive matches, while a bare
                // 3-6 byte sequence that coincidentally occurs inside a compressed
                // image/content stream (common in large scanned PDFs) does not.
                foreach (self::PDF_DANGEROUS_TOKENS as $token) {
                    $pattern = '/'.preg_quote($token, '/').'(?=[\s\/\(\)<>\[\]%]|$)/';
                    if (preg_match($pattern, $contents) === 1) {
                        $errors[] = 'يحتوي ملف PDF على محتوى تفاعلي أو برمجي غير مسموح به (مثل جافاسكربت أو إجراءات تلقائية).';
                        break;
                    }
                }
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages(['file' => $errors]);
        }
    }

    /**
     * Generate a WebP variant next to a stored image for smaller,
     * modern-format delivery. Returns a map of format => relative path
     * for whichever variants were successfully created.
     */
    public static function generateImageVariants(string $disk, string $path): array
    {
        if (! function_exists('imagecreatefromstring')) {
            return [];
        }

        $fullPath = Storage::disk($disk)->path($path);
        $info = @getimagesize($fullPath);
        if ($info === false) {
            return [];
        }

        $source = match ($info['mime']) {
            'image/jpeg' => @imagecreatefromjpeg($fullPath),
            'image/png' => @imagecreatefrompng($fullPath),
            'image/gif' => @imagecreatefromgif($fullPath),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($fullPath) : false,
            'image/bmp' => function_exists('imagecreatefrombmp') ? @imagecreatefrombmp($fullPath) : false,
            'image/avif' => function_exists('imagecreatefromavif') ? @imagecreatefromavif($fullPath) : false,
            default => false,
        };

        if (! $source) {
            return [];
        }

        imagepalettetotruecolor($source);
        imagealphablending($source, true);
        imagesavealpha($source, true);

        $pathInfo = pathinfo($path);
        $baseDir = ($pathInfo['dirname'] ?? '.') !== '.' ? $pathInfo['dirname'].'/' : '';
        $variants = [];

        if ($info['mime'] !== 'image/webp' && function_exists('imagewebp')) {
            $relative = $baseDir.$pathInfo['filename'].'.webp';
            if (imagewebp($source, Storage::disk($disk)->path($relative), 82)) {
                $variants['webp'] = $relative;
            }
        }

        imagedestroy($source);

        return $variants;
    }

    public static function deleteVariants(string $disk, array $variants): void
    {
        foreach ($variants as $variantPath) {
            Storage::disk($disk)->delete($variantPath);
        }
    }
}
