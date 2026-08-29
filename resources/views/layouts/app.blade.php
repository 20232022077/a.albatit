<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @php
            $pageTitle = $title ?? $siteSettings->siteName();
            $pageDescription = $metaDescription ?? $siteSettings->siteDescription();
            $pageImage = $ogImage ?? $siteSettings->defaultOgImageUrl();
            $pageCanonical = $canonicalUrl ?? url()->current();
        @endphp

        <title>{{ $pageTitle }}</title>
        @isset($pageDescription)<meta name="description" content="{{ $pageDescription }}">@endisset
        <meta name="robots" content="{{ $robots ?? 'index,follow' }}">
        @if($siteSettings->googleSiteVerification())<meta name="google-site-verification" content="{{ $siteSettings->googleSiteVerification() }}">@endif

        @if($siteSettings->faviconUrl())<link rel="icon" type="image/png" href="{{ $siteSettings->faviconUrl() }}">@else<link rel="icon" href="{{ asset('favicon.ico') }}">@endif

        <link rel="canonical" href="{{ $pageCanonical }}">
        <meta property="og:type" content="{{ $ogType ?? 'website' }}">
        <meta property="og:site_name" content="{{ $siteSettings->siteName() }}">
        <meta property="og:title" content="{{ $pageTitle }}">
        <meta property="og:url" content="{{ $pageCanonical }}">
        @isset($pageDescription)<meta property="og:description" content="{{ $pageDescription }}">@endisset
        @isset($pageImage)<meta property="og:image" content="{{ $pageImage }}">@endisset

        <meta name="twitter:card" content="{{ $pageImage ? 'summary_large_image' : 'summary' }}">
        <meta name="twitter:title" content="{{ $pageTitle }}">
        @isset($pageDescription)<meta name="twitter:description" content="{{ $pageDescription }}">@endisset
        @isset($pageImage)<meta name="twitter:image" content="{{ $pageImage }}">@endisset
        @if($siteSettings->twitterSite())<meta name="twitter:site" content="{{ $siteSettings->twitterSite() }}">@endif

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=tajawal:400,500,700,800,900|amiri-quran:400&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('json-ld')
    </head>
    <body class="min-h-screen overflow-x-hidden bg-slate-50 font-sans text-slate-900 antialiased">
        <a href="#main-content" class="sr-only z-50 rounded-lg bg-emerald-700 px-4 py-2 font-semibold text-white focus:not-sr-only focus:fixed focus:top-3 focus:right-3">تخطَّ إلى المحتوى الرئيسي</a>
        @yield('content')
    </body>
</html>
