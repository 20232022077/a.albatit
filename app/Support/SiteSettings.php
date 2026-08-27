<?php

namespace App\Support;

use App\Models\Media;
use App\Models\Setting;

/**
 * Read-facade over the Setting key-value store, shared into every view as
 * $siteSettings. Keeps the "group.key" naming and Media-id resolution out
 * of Blade templates and controllers.
 */
class SiteSettings
{
    /**
     * A static asset URL with a cache-busting ?v= query string based on the
     * file's mtime. Static images under public/images (the header/footer
     * logo, the homepage hero signature) get replaced in place under the
     * same filename whenever the design changes, and the host serves them
     * with a long browser cache lifetime; without this, visitors who loaded
     * the page before an update keep seeing the old file until their
     * browser's cache expires on its own.
     */
    public function versionedAsset(string $relativePath): string
    {
        $full = public_path($relativePath);
        $version = is_file($full) ? filemtime($full) : time();

        return asset($relativePath).'?v='.$version;
    }

    public function siteName(): string
    {
        return Setting::get('general.site_name') ?: config('app.name');
    }

    public function siteDescription(): ?string
    {
        return Setting::get('general.site_description');
    }

    public function heroEyebrow(): string
    {
        return Setting::get('homepage.hero_eyebrow') ?: 'الموقع الرسمي';
    }

    public function heroImageUrl(): ?string
    {
        return $this->mediaUrl('homepage.hero_image_media_id');
    }

    public function heroCaption(): ?string
    {
        return Setting::get('homepage.hero_caption');
    }

    public function quranCentralitySubtitle(): string
    {
        return Setting::get('pages.quran_centrality_subtitle') ?: 'مقالات ودراسات حول مركزية القرآن الكريم ومحوريته في بناء الأمة.';
    }

    public function quraniyatSubtitle(): string
    {
        return Setting::get('pages.quraniyat_subtitle') ?: 'مقالات وخواطر وفوائد قرآنية.';
    }

    public function wallSubtitle(): string
    {
        return Setting::get('pages.wall_subtitle') ?: 'منشورات ومقالات وخواطر عامة.';
    }

    public function reflectionsSubtitle(): string
    {
        return Setting::get('pages.reflections_subtitle') ?: 'تأملات وتدبرات قرآنية قصيرة.';
    }

    public function booksSubtitle(): string
    {
        return Setting::get('pages.books_subtitle') ?: 'تصفح وحمل الكتب المنشورة.';
    }

    public function programsSubtitle(): string
    {
        return Setting::get('pages.programs_subtitle') ?: 'سلسلة برامج (بناءات واعي) وغيرها من الدروس والمحاضرات.';
    }

    public function lecturesSubtitle(): string
    {
        return Setting::get('pages.lectures_subtitle') ?: 'مكتبة المحاضرات المرئية: تصفح وابحث في أحدث المحاضرات.';
    }

    public function siteEmail(): ?string
    {
        return Setting::get('contact.email');
    }

    public function sitePhone(): ?string
    {
        return Setting::get('contact.phone');
    }

    public function logoUrl(): ?string
    {
        return $this->mediaUrl('branding.logo_media_id');
    }

    /**
     * Always the literal uploaded PNG, never the WebP variant: favicon
     * support for WebP is inconsistent across browsers.
     */
    public function faviconUrl(): ?string
    {
        $id = Setting::get('branding.favicon_media_id');

        return $id ? Media::find($id)?->url() : null;
    }

    public function defaultOgImageUrl(): ?string
    {
        return $this->mediaUrl('seo.default_og_image_id');
    }

    public function twitterSite(): ?string
    {
        return Setting::get('seo.twitter_site');
    }

    public function googleSiteVerification(): ?string
    {
        return Setting::get('seo.google_site_verification');
    }

    public function socialLinks(): array
    {
        $json = Setting::get('social.links');

        return $json ? (json_decode((string) $json, true) ?: []) : [];
    }

    public function itemsPerPage(): int
    {
        return (int) (Setting::get('appearance.items_per_page') ?: 12);
    }

    /**
     * Sections the admin has temporarily hidden from public navigation
     * (header menu + homepage sections). Purely presentational — the
     * underlying pages/routes stay reachable, so an existing shared link
     * or search-engine result never breaks because a section was hidden.
     */
    public function hiddenSections(): array
    {
        $json = Setting::get('navigation.hidden_sections');

        return $json ? (json_decode((string) $json, true) ?: []) : [];
    }

    public function isSectionHidden(string $key): bool
    {
        return in_array($key, $this->hiddenSections(), true);
    }

    private function mediaUrl(string $key): ?string
    {
        $id = Setting::get($key);

        return $id ? Media::find($id)?->displayUrl() : null;
    }
}
