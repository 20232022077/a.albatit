@extends('layouts.public')

@php
    $seo = $item->meta['seo'] ?? [];
    $title = ($seo['title'] ?? $item->title) . ' - ' . config('app.name');
    $metaDescription = $seo['description'] ?? $item->excerpt;
    $book = $item->book;
    $trail = [['الرئيسية', route('home')], ['الكتب', route('books.index')], [$item->title, null]];
    $ogImage = $book?->cover?->url();
    $ogType = 'book';
@endphp

@push('json-ld')
<script type="application/ld+json">{!! json_encode(array_filter([
    '@@context' => 'https://schema.org',
    '@type' => 'Book',
    'name' => $item->title,
    'url' => route('books.show', $item->slug),
    'description' => $item->excerpt,
    'image' => $ogImage,
    'author' => $book?->author_name ? ['@type' => 'Person', 'name' => $book->author_name] : null,
    'datePublished' => $item->published_at?->toDateString(),
    'isbn' => $book?->isbn,
    'numberOfPages' => $book?->pages_count,
    'publisher' => $book?->publisher ? ['@type' => 'Organization', 'name' => $book->publisher] : null,
    'inLanguage' => 'ar',
]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
@endpush

@section('public-content')
<main class="mx-auto max-w-4xl px-5 py-12">
    @include('partials.breadcrumbs')

    <div class="mt-6 flex flex-col gap-6 rounded-3xl border border-emerald-700/10 bg-gradient-to-br from-white to-emerald-50 p-6 shadow-md sm:flex-row sm:p-7">
        <div class="mx-auto w-40 shrink-0 sm:mx-0">
            @if($book?->cover)
                <img src="{{ $book->cover->displayUrl() }}" alt="{{ $item->title }}" class="aspect-[3/4] w-full rounded-2xl object-cover shadow-sm">
            @else
                <div class="grid aspect-[3/4] w-full place-items-center rounded-2xl bg-gradient-to-br from-emerald-50 to-emerald-100 text-emerald-700">
                    <svg class="h-12 w-12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"><rect x="4.5" y="3" width="15" height="18" rx="2"/><path d="M8.5 7.5h7M8.5 11.5h7M8.5 15.5h4.5"/></svg>
                </div>
            @endif

            <div class="mt-4 space-y-2">
                @if($book?->pdf)
                    <a href="{{ $book->pdf->pdfUrl() }}" target="_blank" rel="noopener" class="flex items-center justify-center rounded-lg bg-emerald-800 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">فتح في نافذة جديدة</a>
                @endif
            </div>
        </div>
        <div class="min-w-0 flex-1 text-center sm:text-right">
            <h1 class="text-2xl font-extrabold sm:text-3xl">{{ $item->title }}</h1>
            <span class="mx-auto mt-2 block h-[3px] w-12 rounded-full bg-gradient-to-l from-amber-400 via-amber-300 to-amber-500 sm:mx-0"></span>
            @if($book?->author_name)<p class="mt-1.5 text-lg font-semibold text-emerald-700">{{ $book->author_name }}</p>@endif

            <div class="mt-3 flex flex-wrap items-center justify-center gap-x-5 gap-y-1 text-sm text-slate-500 sm:justify-start">
                @if($item->published_at)<span>تاريخ النشر: {{ $item->published_at->translatedFormat('j F Y') }}</span>@endif
                @if($book?->publisher)<span>الناشر: {{ $book->publisher }}</span>@endif
                @if($book?->publication_year)<span>سنة النشر: {{ $book->publication_year }}</span>@endif
                @if($book?->pages_count)<span>عدد الصفحات: {{ $book->pages_count }}</span>@endif
                @include('partials.share-button', ['inline' => true, 'shareTitle' => $item->title, 'shareUrl' => route('books.show', $item->slug)])
            </div>

            @if($item->categories->isNotEmpty())
                <div class="mt-4 flex flex-wrap justify-center gap-2 sm:justify-start">
                    @foreach($item->categories as $category)<span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">{{ $category->name }}</span>@endforeach
                </div>
            @endif

            @if($item->excerpt)<p class="mt-4 leading-7 text-slate-600">{{ $item->excerpt }}</p>@endif
        </div>
    </div>

    @if($item->body)
        <div class="mt-10">
            <h2 class="text-lg font-bold text-slate-900">نبذة عن الكتاب</h2>
            <div class="mt-4 rounded-3xl border border-emerald-700/10 bg-gradient-to-br from-white to-emerald-50 p-6 shadow-md sm:p-8">
                <div class="prose prose-slate mx-auto max-w-2xl text-[17px] leading-9 text-slate-700">
                    @include('partials.rich-text', ['text' => $item->body, 'accent' => 'emerald'])
                </div>
            </div>
        </div>
    @endif

    @if($item->tags->where('is_active', true)->isNotEmpty())
        <div class="mt-8 flex flex-wrap gap-2">
            @foreach($item->tags->where('is_active', true) as $tag)<span class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-600">#{{ $tag->name }}</span>@endforeach
        </div>
    @endif

    @if($book?->pdf)
        <div class="mt-10">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-lg font-bold text-slate-900">قراءة الكتاب</h2>
                <a href="{{ $book->pdf->pdfUrl() }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 text-sm font-semibold text-emerald-700 transition hover:text-emerald-900">
                    فتح في نافذة جديدة
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 17l-5-5 5-5M5 12h14"/></svg>
                </a>
            </div>
            <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-slate-100">
                <iframe src="{{ $book->pdf->pdfUrl() }}" title="قراءة {{ $item->title }}" loading="lazy" class="h-[70vh] w-full sm:h-[80vh]"></iframe>
            </div>
            <p class="mt-2 text-xs text-slate-400">إذا لم يظهر الكتاب بالأعلى (بعض متصفحات الجوال لا تعرض ملفات PDF داخل الصفحة)، استخدم رابط «فتح في نافذة جديدة» أعلاه.</p>
        </div>
    @endif

    @include('partials.content-navigation', ['prev' => $prev ?? null, 'next' => $next ?? null])
</main>
@endsection
