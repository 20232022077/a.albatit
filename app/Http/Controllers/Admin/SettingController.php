<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Setting;
use App\Support\SafeFileUpload;
use App\Support\SiteSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        $this->authorize('permission', 'settings.manage');

        return view('admin.settings.edit', ['settings' => app(SiteSettings::class)]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('permission', 'settings.manage');

        $data = $request->validate([
            'site_name' => ['nullable', 'string', 'max:255'],
            'site_description' => ['nullable', 'string', 'max:500'],
            'site_email' => ['nullable', 'email', 'max:255'],
            'site_phone' => ['nullable', 'string', 'max:30'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'favicon' => ['nullable', 'image', 'mimes:png', 'max:512'],
            'social' => ['nullable', 'array'],
            'social.*' => ['nullable', 'url', 'max:255'],
            'default_og_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'twitter_site' => ['nullable', 'string', 'max:100'],
            'google_site_verification' => ['nullable', 'string', 'max:255'],
            'items_per_page' => ['nullable', 'integer', 'min:4', 'max:48'],
        ]);

        Setting::set('general.site_name', $data['site_name'] ?? null);
        Setting::set('general.site_description', $data['site_description'] ?? null);
        Setting::set('contact.email', $data['site_email'] ?? null);
        Setting::set('contact.phone', $data['site_phone'] ?? null);
        Setting::set('seo.twitter_site', $data['twitter_site'] ?? null);
        Setting::set('seo.google_site_verification', $data['google_site_verification'] ?? null);
        Setting::set('appearance.items_per_page', $data['items_per_page'] ?? null);

        $social = array_filter($data['social'] ?? []);
        Setting::set('social.links', $social !== [] ? json_encode($social, JSON_UNESCAPED_SLASHES) : null);

        if ($request->hasFile('logo')) {
            $this->replaceMediaSetting('branding.logo_media_id', $request->file('logo'), 'branding', $request->user()->id, SafeFileUpload::IMAGE_EXTENSIONS, 2048);
        }

        if ($request->hasFile('favicon')) {
            $this->replaceMediaSetting('branding.favicon_media_id', $request->file('favicon'), 'branding', $request->user()->id, ['png'], 512, generateVariants: false);
        }

        if ($request->hasFile('default_og_image')) {
            $this->replaceMediaSetting('seo.default_og_image_id', $request->file('default_og_image'), 'seo', $request->user()->id, SafeFileUpload::IMAGE_EXTENSIONS, 4096);
        }

        return back()->with('status', 'تم حفظ إعدادات الموقع.');
    }

    private function replaceMediaSetting(string $key, UploadedFile $file, string $directory, int $userId, array $allowedExtensions, int $maxKb, bool $generateVariants = true): void
    {
        SafeFileUpload::assertSafe($file, $allowedExtensions, $maxKb);

        $path = $file->store($directory, 'public');
        [$width, $height] = @getimagesize($file->getRealPath()) ?: [null, null];
        $variants = ($generateVariants && $width) ? SafeFileUpload::generateImageVariants('public', $path) : [];

        $media = Media::create([
            'uploaded_by' => $userId,
            'disk' => 'public',
            'path' => $path,
            'original_name' => basename($file->getClientOriginalName()),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'width' => $width,
            'height' => $height,
            'metadata' => $variants !== [] ? ['variants' => $variants] : null,
        ]);

        $oldId = Setting::get($key);
        Setting::set($key, (string) $media->id);

        if ($oldId && $old = Media::find($oldId)) {
            Storage::disk($old->disk)->delete($old->path);
            SafeFileUpload::deleteVariants($old->disk, $old->metadata['variants'] ?? []);
            $old->delete();
        }
    }
}
