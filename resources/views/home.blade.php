@extends('layouts.public')

@php
    $typeLabels = ['book' => 'كتاب', 'lecture' => 'محاضرة', 'program' => 'برنامج', 'program_episode' => 'حلقة برنامج', 'reflection' => 'تأمل', 'wall_post' => 'حائط'];
    $typeIcons = [
        'book' => '<rect x="4.5" y="3" width="15" height="18" rx="2"/><path d="M8.5 7.5h7M8.5 11.5h7M8.5 15.5h4.5"/>',
        'lecture' => '<rect x="9" y="2.5" width="6" height="12" rx="3"/><path d="M5.5 10.5a6.5 6.5 0 0 0 13 0M12 19v2.5M8.5 22h7"/>',
        'program' => '<circle cx="12" cy="12" r="9"/><path d="M10 8.5l6 3.5-6 3.5v-7z"/>',
        'program_episode' => '<circle cx="12" cy="12" r="9"/><path d="M10 8.5l6 3.5-6 3.5v-7z"/>',
        'reflection' => '<path d="M9 18.5h6M10 21.5h4M12 3a6.2 6.2 0 0 0-3 11.6c.6.4 1 1.1 1 1.9h4c0-.8.4-1.5 1-1.9A6.2 6.2 0 0 0 12 3Z"/>',
        'wall_post' => '<path d="M4.5 5.5h15v10h-9L6 20v-4.5h-1.5v-10Z"/>',
        'default' => '<path d="M12 3l2.6 6.2L21 10l-5 4.5 1.3 6.5L12 17.8 6.7 21l1.3-6.5-5-4.5 6.4-.8L12 3z"/>',
    ];
    $title = config('app.name');
    $canonicalUrl = route('home');
@endphp

@push('json-ld')
<script type="application/ld+json">{!! json_encode([
    '@@context' => 'https://schema.org',
    '@type' => 'WebSite',
    'name' => config('app.name'),
    'url' => route('home'),
    'potentialAction' => [
        '@type' => 'SearchAction',
        'target' => route('search') . '?q={search_term_string}',
        'query-input' => 'required name=search_term_string',
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
@endpush

@section('public-content')
<main>
    {{-- ================= HERO ================= --}}
    <section class="relative overflow-hidden bg-brand-gradient-deep text-white">
        <div class="pointer-events-none absolute inset-0 bg-dot-grid-28 opacity-[0.05]"></div>
        <div class="pointer-events-none absolute -top-24 -right-16 h-80 w-80 rounded-full bg-emerald-600/20 blur-3xl"></div>
        <div class="pointer-events-none absolute top-1/2 -left-28 hidden -translate-y-1/2 opacity-[0.07] sm:block">
            <div class="h-64 w-64 rotate-45 rounded-[2.5rem] border-2 border-white"></div>
            <div class="absolute inset-0 h-64 w-64 rounded-[2.5rem] border-2 border-white"></div>
        </div>

        <div class="relative mx-auto max-w-3xl px-5 py-14 text-center sm:py-16 lg:py-20">
            <h1 class="sr-only">{{ $siteSettings->siteName() }}</h1>
            <p class="inline-flex items-center gap-2 text-sm font-bold tracking-[0.3em] text-amber-300/90 sm:text-base">{{ $siteSettings->heroEyebrow() }}</p>
            <img src="{{ $siteSettings->heroImageUrl() ?? $siteSettings->versionedAsset('images/hero-signature-gold.png') }}" alt="{{ $siteSettings->siteName() }}" class="mx-auto mt-6 h-auto w-full max-w-[260px] sm:mt-7 sm:max-w-[360px] lg:mt-8 lg:max-w-[440px]">
            @if($siteSettings->heroCaption())
                <p class="mx-auto mt-6 max-w-xl text-base leading-7 text-emerald-100/90 sm:text-lg">{{ $siteSettings->heroCaption() }}</p>
            @endif
        </div>

        <svg class="relative -mb-px block h-12 w-full text-slate-50" viewBox="0 0 1200 48" preserveAspectRatio="none" fill="currentColor"><path d="M0 48 C300 4 900 4 1200 48 Z"></path></svg>
    </section>

    {{-- ================= BIOGRAPHY ================= --}}
    @if($biography && ! $siteSettings->isSectionHidden('biography'))
        <section class="relative z-10 mx-auto -mt-8 max-w-5xl px-5 sm:-mt-10 lg:px-8">
            <a href="{{ route('biography.show') }}" class="group grid gap-6 overflow-hidden rounded-3xl border border-emerald-700/10 bg-gradient-to-br from-white to-emerald-50 p-6 shadow-xl transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl sm:grid-cols-[auto_1fr_auto] sm:items-center sm:gap-8 sm:p-8">
                <div class="mx-auto sm:mx-0">
                    @if($biography->biography?->profileImage)
                        <div class="rounded-2xl bg-gradient-to-br from-amber-400 via-amber-300 to-amber-500 p-1">
                            <img src="{{ $biography->biography->profileImage->displayUrl() }}" alt="{{ $biography->title }}" class="h-24 w-24 rounded-[0.85rem] object-cover sm:h-28 sm:w-28">
                        </div>
                    @else
                        <span class="grid h-24 w-24 place-items-center rounded-2xl bg-gradient-to-br from-amber-400 via-amber-300 to-amber-500 text-3xl text-emerald-900 sm:h-28 sm:w-28">۞</span>
                    @endif
                </div>
                <div class="min-w-0 text-center sm:text-right">
                    <h2 class="text-xl font-extrabold text-slate-900 sm:text-2xl">{{ $biography->title }}</h2>
                    <p class="mt-2 leading-7 text-slate-600">{{ $biography->excerpt }}</p>
                </div>
                <span class="mx-auto flex w-fit shrink-0 items-center gap-1.5 rounded-full bg-emerald-50 px-4 py-2 text-sm font-bold text-emerald-800 ring-1 ring-emerald-100 transition group-hover:gap-2.5 group-hover:bg-emerald-800 group-hover:text-white sm:mx-0">
                    السيرة كاملة
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 17l-5-5 5-5M5 12h14"/></svg>
                </span>
            </a>
        </section>
    @endif

    {{-- ================= QURAN CENTRALITY — scholarly, editorial ================= --}}
    @unless($siteSettings->isSectionHidden('quran-centrality'))
    <section id="quran-centrality" class="bg-slate-50">
        <div class="mx-auto max-w-7xl px-5 py-16 lg:px-8">
            @include('partials.section-heading', [
                'title' => 'مركزية القرآن',
                'icon' => '۞',
                'accent' => 'slate',
                'action' => ['label' => 'كل المحتوى', 'url' => route('quran-centrality.index')],
            ])

            <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @forelse($quranCentrality as $item)
                    @php $cardVis = match(true) { $loop->index === 0 => 'flex', $loop->index === 1 => 'hidden sm:flex', default => 'hidden lg:flex' }; @endphp
                    <a href="{{ route('quran-centrality.show', $item->slug) }}" class="group relative {{ $cardVis }} flex-col overflow-hidden rounded-2xl border border-emerald-700/10 bg-gradient-to-br from-white to-emerald-50 shadow-md transition-all duration-300 hover:-translate-y-2 hover:border-amber-300/40 hover:shadow-2xl">
                        @if($cover = $item->coverImage())
                            <div class="aspect-[4/3] overflow-hidden">
                                <img loading="lazy" src="{{ $cover->displayUrl() }}" alt="{{ $item->title }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                            </div>
                        @endif
                        <div class="flex flex-1 flex-col p-5">
                            <span class="inline-flex w-fit items-center rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-bold tracking-wide text-emerald-700 ring-1 ring-emerald-100">مركزية القرآن</span>
                            <div class="mt-2.5 flex items-start justify-between gap-1.5">
                                <h3 class="min-w-0 flex-1 line-clamp-2 text-base font-bold leading-6">{{ $item->title }}</h3>
                                @include('partials.share-button', ['inline' => true, 'shareTitle' => $item->title, 'shareUrl' => route('quran-centrality.show', $item->slug)])
                            </div>
                            @include('partials.card-divider', ['accent' => 'slate'])
                            <p class="line-clamp-3 flex-1 text-sm leading-6 text-slate-600">{!! \App\Support\QuranTextHighlighter::highlightExcerpt((string) $item->body, 130, 'slate') !!}</p>
                            <span class="mt-3 inline-flex items-center gap-1 text-xs font-bold text-emerald-700 transition group-hover:gap-1.5">
                                قراءة المزيد
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 17l-5-5 5-5M5 12h14"/></svg>
                            </span>
                        </div>
                    </a>
                @empty
                    <p class="col-span-full rounded-2xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">لا يوجد محتوى منشور في هذا القسم حاليًا.</p>
                @endforelse
            </div>
        </div>
    </section>
    @endunless

    {{-- ================= QURANIYAT — devotional, ornamental ================= --}}
    @unless($siteSettings->isSectionHidden('quraniyat'))
    <section id="quraniyat" class="bg-gradient-to-b from-amber-50/40 via-white to-white">
        <div class="mx-auto max-w-7xl px-5 py-16 lg:px-8">
            @include('partials.section-heading', [
                'title' => 'قرآنيات',
                'icon' => '✦',
                'accent' => 'amber',
                'action' => ['label' => 'كل القرآنيات', 'url' => route('quraniyat.index')],
            ])

            <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @forelse($quraniyat as $item)
                    @php $cardVis = match(true) { $loop->index === 0 => 'flex', $loop->index === 1 => 'hidden sm:flex', default => 'hidden lg:flex' }; @endphp
                    <a href="{{ route('quraniyat.show', $item->slug) }}" class="group relative {{ $cardVis }} flex-col overflow-hidden rounded-2xl border border-emerald-700/10 bg-gradient-to-br from-white to-amber-50 shadow-md transition-all duration-300 hover:-translate-y-2 hover:border-amber-300/40 hover:shadow-2xl">
                        <span class="absolute inset-x-6 top-0 z-10 h-px bg-gradient-to-l from-amber-400/70 via-amber-300/70 to-transparent"></span>

                        @if($cover = $item->coverImage())
                            <div class="aspect-[3/1] overflow-hidden">
                                <img loading="lazy" src="{{ $cover->displayUrl() }}" alt="{{ $item->title }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                            </div>
                        @endif

                        <div class="relative flex flex-1 flex-col bg-transparent p-6 pt-7">
                            <span class="inline-flex w-fit items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-700 ring-1 ring-emerald-100">✦ قرآنيات</span>
                            <div class="mt-3 flex items-start justify-between gap-1.5">
                                <h3 class="min-w-0 flex-1 text-lg font-bold leading-7">{{ $item->title }}</h3>
                                @include('partials.share-button', ['inline' => true, 'shareTitle' => $item->title, 'shareUrl' => route('quraniyat.show', $item->slug)])
                            </div>
                            @include('partials.card-divider', ['accent' => 'amber'])
                            <p class="line-clamp-3 flex-1 text-sm leading-7 text-slate-600">{{ \Illuminate\Support\Str::limit((string) $item->body, 120) }}</p>
                            <span class="mt-4 inline-flex items-center gap-1 text-xs font-bold text-emerald-700 transition group-hover:gap-1.5">
                                قراءة المزيد
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 17l-5-5 5-5M5 12h14"/></svg>
                            </span>
                        </div>
                    </a>
                @empty
                    <p class="col-span-full rounded-2xl border border-dashed border-amber-200 bg-white/60 p-8 text-center text-sm text-slate-500">لا يوجد محتوى منشور في هذا القسم حاليًا.</p>
                @endforelse
            </div>
        </div>
    </section>
    @endunless

    {{-- ================= WALL — lively feed, pinned emphasis ================= --}}
    @unless($siteSettings->isSectionHidden('wall'))
    <section id="wall" class="bg-slate-50">
        <div class="mx-auto max-w-7xl px-5 py-16 lg:px-8">
            @include('partials.section-heading', [
                'title' => 'حائط',
                'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">'.$typeIcons['wall_post'].'</svg>',
                'action' => ['label' => 'كل المنشورات', 'url' => route('wall.index')],
            ])

            @if($wallPosts->isNotEmpty())
                <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach($wallPosts as $item)
                        @php $cardVis = match(true) { $loop->index === 0 => 'flex', $loop->index === 1 => 'hidden sm:flex', default => 'hidden lg:flex' }; @endphp
                        <a href="{{ \App\Support\ContentUrl::for($item) }}" class="group relative {{ $cardVis }} flex-col overflow-hidden rounded-2xl border border-emerald-700/10 bg-gradient-to-br from-white via-white to-emerald-100/60 shadow-md transition-all duration-300 hover:-translate-y-2 hover:border-amber-300/40 hover:shadow-2xl">
                            <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-3">
                                @if($item->is_pinned)<span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-bold text-amber-800">📌 مثبّت</span>@endif
                                <p class="text-xs text-slate-400">{{ $item->published_at?->translatedFormat('j F Y') }}</p>
                            </div>
                            <div class="flex flex-1 flex-col px-5 py-4">
                                <div class="flex items-start justify-between gap-1.5">
                                    <h3 class="min-w-0 flex-1 line-clamp-2 text-base font-extrabold leading-7 text-slate-800">{{ $item->title }}</h3>
                                    @include('partials.share-button', ['inline' => true, 'shareTitle' => $item->title, 'shareUrl' => \App\Support\ContentUrl::for($item)])
                                </div>
                                @include('partials.card-divider', ['accent' => 'emerald'])
                                <p class="line-clamp-2 flex-1 text-sm leading-7 text-slate-600">{{ \Illuminate\Support\Str::limit((string) $item->body, 110) }}</p>
                                <span class="mt-3 inline-flex items-center gap-1.5 text-sm font-bold text-emerald-700 transition group-hover:gap-2.5">
                                    قراءة المنشور
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 17l-5-5 5-5M5 12h14"/></svg>
                                </span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <p class="mt-8 rounded-2xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">لا يوجد محتوى منشور في هذا القسم حاليًا.</p>
            @endif
        </div>
    </section>
    @endunless

    {{-- ================= REFLECTIONS — quiet, article-style ================= --}}
    @unless($siteSettings->isSectionHidden('reflections'))
    <section id="reflections" class="bg-white">
        <div class="mx-auto max-w-7xl px-5 py-16 lg:px-8">
            @include('partials.section-heading', [
                'title' => 'تأملات',
                'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">'.$typeIcons['reflection'].'</svg>',
                'accent' => 'amber',
                'action' => ['label' => 'كل التأملات', 'url' => route('reflections.index')],
            ])

            <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @forelse($reflections as $item)
                    @php $cardVis = match(true) { $loop->index === 0 => 'flex', $loop->index === 1 => 'hidden sm:flex', default => 'hidden lg:flex' }; @endphp
                    <a href="{{ \App\Support\ContentUrl::for($item) }}" class="group relative {{ $cardVis }} flex-col overflow-hidden rounded-2xl border border-emerald-700/10 bg-gradient-to-br from-white to-amber-50 shadow-md transition-all duration-300 hover:-translate-y-2 hover:border-amber-300/40 hover:shadow-2xl">
                        @if($cover = $item->coverImage())
                            <div class="aspect-[3/1] overflow-hidden">
                                <img loading="lazy" src="{{ $cover->displayUrl() }}" alt="{{ $item->title }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                            </div>
                        @endif
                        <div class="flex flex-1 flex-col bg-transparent p-6">
                            @unless($cover)
                                <span class="text-3xl leading-none text-amber-300/80" aria-hidden="true">”</span>
                            @endunless
                            <div class="{{ $cover ? '' : '-mt-1' }} flex items-start justify-between gap-1.5">
                                <h3 class="min-w-0 flex-1 text-lg font-bold leading-7">{{ $item->title }}</h3>
                                @include('partials.share-button', ['inline' => true, 'shareTitle' => $item->title, 'shareUrl' => \App\Support\ContentUrl::for($item)])
                            </div>
                            @include('partials.card-divider', ['accent' => 'amber'])
                            <p class="line-clamp-3 flex-1 text-sm leading-7 text-slate-600">{{ \Illuminate\Support\Str::limit((string) $item->body, 140) }}</p>
                            <div class="mt-4 flex items-center justify-between gap-2">
                                <span class="text-xs font-semibold text-slate-500">{{ $item->published_at?->translatedFormat('j F Y') }}</span>
                                <span class="inline-flex items-center gap-1 text-sm font-bold text-emerald-700 transition group-hover:gap-2">
                                    قراءة المزيد
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 17l-5-5 5-5M5 12h14"/></svg>
                                </span>
                            </div>
                        </div>
                    </a>
                @empty
                    <p class="col-span-full rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500">لا توجد تأملات منشورة حاليًا.</p>
                @endforelse
            </div>
        </div>
    </section>
    @endunless

    {{-- ================= BOOKS — digital library shelf ================= --}}
    @unless($siteSettings->isSectionHidden('books'))
    <section class="mx-auto max-w-7xl px-5 py-16 lg:px-8">
        @include('partials.section-heading', [
            'title' => 'الكتب',
            'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">'.$typeIcons['book'].'</svg>',
            'accent' => 'amber',
            'action' => ['label' => 'عرض كل الكتب', 'url' => route('books.index')],
        ])

        <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @forelse($books as $item)
                @php $cardVis = match(true) { $loop->index === 0 => 'flex', $loop->index === 1 => 'hidden sm:flex', default => 'hidden lg:flex' }; @endphp
                <a href="{{ \App\Support\ContentUrl::for($item) }}" class="group relative {{ $cardVis }} flex-col overflow-hidden rounded-2xl border border-emerald-700/10 bg-gradient-to-br from-white to-emerald-50 shadow-md transition-all duration-300 hover:-translate-y-2 hover:border-amber-300/40 hover:shadow-2xl">
                    <span class="absolute inset-x-0 top-0 z-10 h-1 bg-gradient-to-l from-amber-400 via-amber-300 to-amber-100"></span>
                    @if($item->book?->cover)
                        <div class="aspect-[3/4] overflow-hidden">
                            <img loading="lazy" src="{{ $item->book->cover->displayUrl() }}" alt="{{ $item->title }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                        </div>
                        <div class="p-4">
                            @if($item->book?->author_name)<p class="text-[11px] font-bold tracking-wide text-amber-700">{{ $item->book->author_name }}</p>@endif
                            <div class="mt-1.5 flex items-start justify-between gap-1.5">
                                <h3 class="min-w-0 flex-1 line-clamp-1 font-bold">{{ $item->title }}</h3>
                                @include('partials.share-button', ['inline' => true, 'shareTitle' => $item->title, 'shareUrl' => \App\Support\ContentUrl::for($item)])
                            </div>
                            <span class="mt-1.5 block h-[3px] w-8 rounded-full bg-gradient-to-l from-amber-400 via-amber-300 to-amber-500"></span>
                            <span class="mt-2 inline-flex items-center gap-1 text-xs font-bold text-emerald-700 transition group-hover:gap-1.5">
                                قراءة الكتاب
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 17l-5-5 5-5M5 12h14"/></svg>
                            </span>
                        </div>
                    @else
                        <div class="flex flex-1 flex-col justify-center bg-gradient-to-br from-white to-emerald-50 p-5 text-center">
                            @if($item->book?->author_name)<p class="text-[11px] font-bold tracking-wide text-amber-700">{{ $item->book->author_name }}</p>@endif
                            <div class="mt-2 flex items-start justify-center gap-1.5">
                                <h3 class="line-clamp-3 font-bold leading-6">{{ $item->title }}</h3>
                                @include('partials.share-button', ['inline' => true, 'shareTitle' => $item->title, 'shareUrl' => \App\Support\ContentUrl::for($item)])
                            </div>
                            <span class="mx-auto mt-1.5 block h-[3px] w-8 rounded-full bg-gradient-to-l from-amber-400 via-amber-300 to-amber-500"></span>
                            <span class="mx-auto mt-2 inline-flex items-center gap-1 text-xs font-bold text-emerald-700 transition group-hover:gap-1.5">
                                قراءة الكتاب
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 17l-5-5 5-5M5 12h14"/></svg>
                            </span>
                        </div>
                    @endif
                </a>
            @empty
                <p class="col-span-full rounded-2xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">لا توجد كتب منشورة حاليًا.</p>
            @endforelse
        </div>
    </section>
    @endunless

    {{-- ================= PROGRAMS — organized, structured list cards ================= --}}
    @unless($siteSettings->isSectionHidden('programs'))
    <section id="programs" class="bg-slate-50">
        <div class="mx-auto max-w-7xl px-5 py-16 lg:px-8">
            @include('partials.section-heading', [
                'title' => 'البرامج',
                'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M10 8.5l6 3.5-6 3.5v-7z"/></svg>',
                'accent' => 'slate',
                'action' => ['label' => 'كل البرامج', 'url' => route('programs.index')],
            ])

            <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @forelse($programs as $item)
                    @php $cardVis = match(true) { $loop->index === 0 => 'flex', $loop->index === 1 => 'hidden sm:flex', default => 'hidden lg:flex' }; @endphp
                    <a href="{{ \App\Support\ContentUrl::for($item) }}" class="group relative {{ $cardVis }} flex-col overflow-hidden rounded-2xl border border-emerald-700/10 bg-gradient-to-br from-white to-emerald-50 shadow-md transition-all duration-300 hover:-translate-y-2 hover:border-amber-300/40 hover:shadow-2xl">
                        <div class="aspect-video overflow-hidden bg-slate-100">
                            @if($cover = $item->coverImage())
                                <img loading="lazy" src="{{ $cover->displayUrl() }}" alt="{{ $item->title }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                            @else
                                <div class="grid h-full w-full place-items-center text-slate-300">
                                    <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">{!! $typeIcons['program'] !!}</svg>
                                </div>
                            @endif
                        </div>
                        <div class="flex flex-1 flex-col p-4">
                            <span class="inline-flex w-fit items-center rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-bold tracking-wide text-emerald-700 ring-1 ring-emerald-100">برنامج</span>
                            <div class="mt-2 flex items-start justify-between gap-1.5">
                                <h3 class="min-w-0 flex-1 line-clamp-2 text-sm font-bold leading-6">{{ $item->title }}</h3>
                                @include('partials.share-button', ['inline' => true, 'shareTitle' => $item->title, 'shareUrl' => \App\Support\ContentUrl::for($item)])
                            </div>
                            <span class="mt-1.5 block h-[3px] w-8 rounded-full bg-gradient-to-l from-amber-400 via-amber-300 to-amber-500"></span>
                            @if($item->program?->presenter)
                                <div class="mt-auto flex items-center gap-1.5 border-t border-slate-100 pt-3 text-xs font-semibold text-slate-500">
                                    <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.4"/><path d="M5 20c1-3.6 4-5.6 7-5.6s6 2 7 5.6"/></svg>
                                    <span class="truncate">{{ $item->program->presenter }}</span>
                                </div>
                            @endif
                        </div>
                    </a>
                @empty
                    <p class="col-span-full rounded-2xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">لا توجد برامج منشورة حاليًا.</p>
                @endforelse
            </div>
        </div>
    </section>
    @endunless

    {{-- ================= LECTURES — same card model as Programs ================= --}}
    @unless($siteSettings->isSectionHidden('lectures'))
    <section id="lectures" class="bg-white">
        <div class="mx-auto max-w-7xl px-5 py-16 lg:px-8">
            @include('partials.section-heading', [
                'title' => 'المحاضرات',
                'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">'.$typeIcons['lecture'].'</svg>',
                'action' => ['label' => 'كل المحاضرات', 'url' => route('lectures.index')],
            ])

            <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @forelse($lectures as $item)
                    @php $cardVis = match(true) { $loop->index === 0 => 'flex', $loop->index === 1 => 'hidden sm:flex', default => 'hidden lg:flex' }; @endphp
                    <a href="{{ \App\Support\ContentUrl::for($item) }}" class="group relative {{ $cardVis }} flex-col overflow-hidden rounded-2xl border border-emerald-700/10 bg-gradient-to-br from-white to-emerald-50 shadow-md transition-all duration-300 hover:-translate-y-2 hover:border-amber-300/40 hover:shadow-2xl">
                        <div class="aspect-video overflow-hidden bg-slate-100">
                            @if($cover = $item->coverImage())
                                <img loading="lazy" src="{{ $cover->displayUrl() }}" alt="{{ $item->title }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                            @else
                                <div class="grid h-full w-full place-items-center text-slate-300">
                                    <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">{!! $typeIcons['lecture'] !!}</svg>
                                </div>
                            @endif
                        </div>
                        <div class="flex flex-1 flex-col p-4">
                            <span class="inline-flex w-fit items-center rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-bold tracking-wide text-emerald-700 ring-1 ring-emerald-100">محاضرة</span>
                            <div class="mt-2 flex items-start justify-between gap-1.5">
                                <h3 class="min-w-0 flex-1 line-clamp-2 text-sm font-bold leading-6">{{ $item->title }}</h3>
                                @include('partials.share-button', ['inline' => true, 'shareTitle' => $item->title, 'shareUrl' => \App\Support\ContentUrl::for($item)])
                            </div>
                            <span class="mt-1.5 block h-[3px] w-8 rounded-full bg-gradient-to-l from-amber-400 via-amber-300 to-amber-500"></span>
                            @if($item->lecture?->speaker)
                                <div class="mt-auto flex items-center gap-1.5 border-t border-slate-100 pt-3 text-xs font-semibold text-slate-500">
                                    <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.4"/><path d="M5 20c1-3.6 4-5.6 7-5.6s6 2 7 5.6"/></svg>
                                    <span class="truncate">{{ $item->lecture->speaker }}</span>
                                </div>
                            @endif
                        </div>
                    </a>
                @empty
                    <p class="col-span-full rounded-2xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">لا توجد محاضرات منشورة حاليًا.</p>
                @endforelse
            </div>
        </div>
    </section>
    @endunless
</main>
@endsection
