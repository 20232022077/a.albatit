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
    public function siteName(): string
    {
        return Setting::get('general.site_name') ?: config('app.name');
    }

    public function siteDescription(): ?string
    {
        return Setting::get('general.site_description');
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

    private function mediaUrl(string $key): ?string
    {
        $id = Setting::get($key);

        return $id ? Media::find($id)?->displayUrl() : null;
    }
}
