@extends('layouts.public')

@php
    $contentItem = $biography->contentItem;
    $title = config('app.name');
    $metaDescription = $contentItem->excerpt;
    $trail = [['الرئيسية', route('home')], ['السيرة الذاتية', null]];
    $ogImage = $biography->profileImage?->url();
    $meta = $contentItem->meta ?? [];
    $socialLinks = $meta['social_links'] ?? [];
    $socialLabels = ['twitter' => 'X (تويتر)', 'x' => 'X (تويتر)', 'youtube' => 'يوتيوب', 'facebook' => 'فيسبوك', 'instagram' => 'إنستغرام', 'telegram' => 'تيليجرام', 'snapchat' => 'سناب شات', 'tiktok' => 'تيك توك', 'website' => 'الموقع'];
    $basicInfo = array_filter([
        ['label' => $meta['city'] ?? null],
        ['label' => filled($meta['birth_year_hijri'] ?? null) ? 'مواليد '.$meta['birth_year_hijri'].' هـ' : null],
        ['label' => $meta['contact_email'] ?? null, 'href' => filled($meta['contact_email'] ?? null) ? 'mailto:'.$meta['contact_email'] : null],
        ['label' => $meta['contact_phone'] ?? null, 'href' => filled($meta['contact_phone'] ?? null) ? 'tel:'.$meta['contact_phone'] : null],
    ], fn ($row) => filled($row['label']));
    $groups = [
        'qualification' => ['label' => 'المؤهلات العلمية'],
        'work' => ['label' => 'الخبرات المهنية'],
        'development' => ['label' => 'الأنشطة العلمية والتطويرية'],
        'teaching' => ['label' => 'الأنشطة التعليمية الحالية'],
        'achievement' => ['label' => 'الإنجازات'],
    ];
    $visibleSections = $biography->sections->where('is_visible', true);
@endphp

@push('json-ld')
<script type="application/ld+json">{!! json_encode(array_filter([
    '@@context' => 'https://schema.org',
    '@type' => 'Person',
    'name' => $contentItem->title,
    'url' => route('biography.show'),
    'description' => $contentItem->excerpt,
    'image' => $ogImage,
    'email' => $meta['contact_email'] ?? null,
    'telephone' => $meta['contact_phone'] ?? null,
    'address' => filled($meta['city'] ?? null) ? ['@type' => 'PostalAddress', 'addressLocality' => $meta['city']] : null,
    'sameAs' => array_values(array_filter($socialLinks)) ?: null,
]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
@endpush

@section('public-content')
<section class="relative overflow-hidden bg-brand-gradient-deep text-white">
    <div class="pointer-events-none absolute -top-24 -right-24 h-96 w-96 rounded-full bg-amber-400/10 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-32 -left-24 h-96 w-96 rounded-full bg-emerald-400/10 blur-3xl"></div>

    <div class="relative mx-auto grid max-w-5xl gap-10 px-5 py-16 lg:grid-cols-[260px_1fr] lg:items-center lg:gap-12 lg:px-8 lg:py-24">
        <div class="mx-auto w-52 sm:w-60 lg:mx-0 lg:w-full">
            <div class="relative rounded-[2rem] bg-gradient-to-br from-amber-400 via-amber-300 to-amber-500 p-1.5 shadow-2xl">
                @if($biography->profileImage)
                    <img src="{{ $biography->profileImage->displayUrl() }}" alt="{{ $contentItem->title }}" class="aspect-square w-full rounded-[1.7rem] object-cover">
                @else
                    <div class="aspect-square w-full rounded-[1.7rem] bg-emerald-800"></div>
                @endif
            </div>
        </div>

        <div class="text-center lg:text-right">
            <h1 class="text-4xl font-extrabold tracking-tight sm:text-5xl">{{ $contentItem->title }} @include('partials.share-button', ['inline' => true, 'dark' => true, 'shareTitle' => $contentItem->title, 'shareUrl' => route('biography.show')])</h1>
            @if($contentItem->excerpt)<p class="mx-auto mt-4 max-w-xl text-lg leading-8 text-emerald-100 lg:mx-0">{{ $contentItem->excerpt }}</p>@endif

            @if(filled($basicInfo))
                <ul class="mt-6 flex flex-wrap justify-center divide-x divide-x-reverse divide-emerald-700/60 text-sm text-emerald-100 lg:justify-start">
                    @foreach($basicInfo as $row)
                        <li class="px-3 first:pr-0 last:pl-0">
                            @if(!empty($row['href']))
                                <a href="{{ $row['href'] }}" dir="ltr" class="transition hover:text-amber-300">{{ $row['label'] }}</a>
                            @else
                                <span>{{ $row['label'] }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif

            @if(filled($socialLinks))
                <div class="mt-6 flex flex-wrap justify-center gap-3 lg:justify-start">
                    @foreach($socialLinks as $key => $url)
                        @if(filled($url))
                            <a href="{{ $url }}" target="_blank" rel="noopener" class="rounded-full bg-white/10 px-4 py-2 text-sm font-semibold text-white ring-1 ring-amber-300/30 transition hover:-translate-y-0.5 hover:bg-amber-400/20">{{ $socialLabels[strtolower($key)] ?? $key }}</a>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <svg class="relative block h-10 w-full text-slate-50" viewBox="0 0 1200 40" preserveAspectRatio="none" fill="currentColor"><path d="M0 40 C300 0 900 0 1200 40 Z"></path></svg>
</section>

<main class="mx-auto -mt-2 max-w-4xl px-5 pb-16 lg:px-8">
    <div class="pt-6">@include('partials.breadcrumbs')</div>

    @if($contentItem->body)
        <section class="rounded-3xl border border-emerald-700/10 bg-gradient-to-br from-white to-emerald-50 p-8 shadow-md">
            <p class="text-sm font-bold text-amber-700">نبذة</p>
            <div class="prose prose-slate mt-3 max-w-none leading-8">{!! nl2br(e($contentItem->body)) !!}</div>
        </section>
    @endif

    @if($books->isNotEmpty())
        <section class="mt-14">
            <h2 class="border-r-4 border-amber-400 pr-4 text-2xl font-extrabold">الكتب التي ألّفها</h2>
            <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($books as $item)
                    <a href="{{ route('books.show', $item->slug) }}" class="group relative block overflow-hidden rounded-3xl border border-emerald-700/10 bg-gradient-to-br from-white to-emerald-50 shadow-md transition-all duration-300 hover:-translate-y-2 hover:border-amber-300/40 hover:shadow-2xl">
                        <span class="absolute inset-x-0 top-0 z-10 h-1 bg-gradient-to-l from-amber-400 via-amber-300 to-amber-100"></span>
                        @if($item->book?->cover)
                            <img loading="lazy" src="{{ $item->book->cover->displayUrl() }}" alt="{{ $item->title }}" class="h-52 w-full object-cover transition duration-500 group-hover:scale-105">
                        @else
                            <div class="h-52 w-full bg-amber-50"></div>
                        @endif
                        <div class="p-5">
                            <div class="flex items-start justify-between gap-1.5">
                                <h3 class="min-w-0 flex-1 text-lg font-bold">{{ $item->title }}</h3>
                                @include('partials.share-button', ['inline' => true, 'shareTitle' => $item->title, 'shareUrl' => route('books.show', $item->slug)])
                            </div>
                            <span class="mt-1.5 block h-[3px] w-8 rounded-full bg-gradient-to-l from-amber-400 via-amber-300 to-amber-500"></span>
                            @if($item->excerpt)<p class="mt-2 line-clamp-2 text-sm leading-6 text-slate-600">{{ $item->excerpt }}</p>@endif
                            <span class="mt-3 inline-flex items-center gap-1 text-xs font-bold text-emerald-700 transition group-hover:gap-1.5">
                                قراءة الكتاب
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 17l-5-5 5-5M5 12h14"/></svg>
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @foreach($groups as $type => $group)
        @php $sections = $visibleSections->where('type', $type)->sortBy('sort_order'); @endphp
        @if($sections->isNotEmpty())
            <section class="mt-14">
                <h2 class="border-r-4 border-amber-400 pr-4 text-2xl font-extrabold">{{ $group['label'] }}</h2>

                <div class="relative mt-8">
                    <div class="absolute bottom-2 top-2 right-[7px] w-0.5 bg-amber-200"></div>
                    <div class="space-y-6">
                        @foreach($sections as $section)
                            <div class="relative pr-8">
                                <span class="absolute right-0 top-1.5 h-4 w-4 rounded-full border-[3px] border-amber-400 bg-white shadow-[0_0_0_3px_white]"></span>
                                <div class="rounded-2xl border border-emerald-700/10 bg-gradient-to-br from-white to-emerald-50 p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-amber-300/40 hover:shadow-md">
                                    @if($sections->count() > 1)
                                        <h3 class="font-bold text-slate-800">{{ $section->title }}</h3>
                                    @endif
                                    @if($section->body)
                                        <div class="mt-2 leading-7 text-slate-600">
                                            @include('partials.rich-text', ['text' => $section->body, 'accent' => 'amber'])
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif
    @endforeach
</main>
@endsection
