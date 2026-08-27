<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Setting;
use App\Support\ActivityLogger;
use App\Support\SafeFileUpload;
use App\Support\SiteSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
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
            'hero_eyebrow' => ['nullable', 'string', 'max:60'],
            'hero_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.SafeFileUpload::MAX_IMAGE_KB],
            'hero_caption' => ['nullable', 'string', 'max:200'],
            'quran_centrality_subtitle' => ['nullable', 'string', 'max:300'],
            'quraniyat_subtitle' => ['nullable', 'string', 'max:300'],
            'wall_subtitle' => ['nullable', 'string', 'max:300'],
            'reflections_subtitle' => ['nullable', 'string', 'max:300'],
            'books_subtitle' => ['nullable', 'string', 'max:300'],
            'programs_subtitle' => ['nullable', 'string', 'max:300'],
            'lectures_subtitle' => ['nullable', 'string', 'max:300'],
            'site_email' => ['nullable', 'email', 'max:255'],
            'site_phone' => ['nullable', 'string', 'max:30'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.SafeFileUpload::MAX_LOGO_KB],
            'favicon' => ['nullable', 'image', 'mimes:png', 'max:'.SafeFileUpload::MAX_FAVICON_KB],
            'social' => ['nullable', 'array'],
            'social.*' => ['nullable', 'url', 'max:255'],
            'default_og_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.SafeFileUpload::MAX_IMAGE_KB],
            'twitter_site' => ['nullable', 'string', 'max:100'],
            'google_site_verification' => ['nullable', 'string', 'max:255'],
            'items_per_page' => ['nullable', 'integer', 'min:4', 'max:48'],
            'hidden_sections' => ['nullable', 'array'],
            'hidden_sections.*' => ['string', Rule::in(['biography', 'quran-centrality', 'books', 'quraniyat', 'programs', 'lectures', 'reflections', 'wall'])],
        ]);

        Setting::set('general.site_name', $data['site_name'] ?? null);
        Setting::set('general.site_description', $data['site_description'] ?? null);
        Setting::set('homepage.hero_eyebrow', $data['hero_eyebrow'] ?? null);
        Setting::set('homepage.hero_caption', $data['hero_caption'] ?? null);
        Setting::set('pages.quran_centrality_subtitle', $data['quran_centrality_subtitle'] ?? null);
        Setting::set('pages.quraniyat_subtitle', $data['quraniyat_subtitle'] ?? null);
        Setting::set('pages.wall_subtitle', $data['wall_subtitle'] ?? null);
        Setting::set('pages.reflections_subtitle', $data['reflections_subtitle'] ?? null);
        Setting::set('pages.books_subtitle', $data['books_subtitle'] ?? null);
        Setting::set('pages.programs_subtitle', $data['programs_subtitle'] ?? null);
        Setting::set('pages.lectures_subtitle', $data['lectures_subtitle'] ?? null);
        Setting::set('contact.email', $data['site_email'] ?? null);
        Setting::set('contact.phone', $data['site_phone'] ?? null);
        Setting::set('seo.twitter_site', $data['twitter_site'] ?? null);
        Setting::set('seo.google_site_verification', $data['google_site_verification'] ?? null);
        Setting::set('appearance.items_per_page', $data['items_per_page'] ?? null);

        $hiddenSections = array_values(array_unique($data['hidden_sections'] ?? []));
        Setting::set('navigation.hidden_sections', $hiddenSections !== [] ? json_encode($hiddenSections) : null);

        $social = array_filter($data['social'] ?? []);
        Setting::set('social.links', $social !== [] ? json_encode($social, JSON_UNESCAPED_SLASHES) : null);

        if ($request->hasFile('logo')) {
            $this->replaceMediaSetting('branding.logo_media_id', $request->file('logo'), 'branding', $request->user()->id, SafeFileUpload::IMAGE_EXTENSIONS, SafeFileUpload::MAX_LOGO_KB);
        }

        if ($request->hasFile('hero_image')) {
            $this->replaceMediaSetting('homepage.hero_image_media_id', $request->file('hero_image'), 'homepage', $request->user()->id, SafeFileUpload::IMAGE_EXTENSIONS, SafeFileUpload::MAX_IMAGE_KB);
        }

        if ($request->hasFile('favicon')) {
            $this->replaceMediaSetting('branding.favicon_media_id', $request->file('favicon'), 'branding', $request->user()->id, ['png'], SafeFileUpload::MAX_FAVICON_KB, generateVariants: false);
        }

        if ($request->hasFile('default_og_image')) {
            $this->replaceMediaSetting('seo.default_og_image_id', $request->file('default_og_image'), 'seo', $request->user()->id, SafeFileUpload::IMAGE_EXTENSIONS, SafeFileUpload::MAX_IMAGE_KB);
        }

        ActivityLogger::log('settings.updated', properties: [
            'site_name' => $data['site_name'] ?? null,
            'changed_files' => array_keys(array_filter([
                'logo' => $request->hasFile('logo'),
                'hero_image' => $request->hasFile('hero_image'),
                'favicon' => $request->hasFile('favicon'),
                'default_og_image' => $request->hasFile('default_og_image'),
            ])),
        ]);

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
