<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? config('app.name') }}</title>
        <meta name="robots" content="noindex,nofollow">
        @if($siteSettings->faviconUrl())<link rel="icon" type="image/png" href="{{ $siteSettings->faviconUrl() }}">@else<link rel="icon" href="{{ asset('favicon.ico') }}">@endif

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=tajawal:400,500,700,800,900&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css'])
    </head>
    <body class="grid min-h-screen place-items-center overflow-x-hidden bg-gradient-to-bl from-emerald-950 via-emerald-900 to-teal-900 px-5 py-16 font-sans text-white antialiased">
        <div class="mx-auto w-full max-w-lg text-center">
            @yield('content')
        </div>
    </body>
</html>
