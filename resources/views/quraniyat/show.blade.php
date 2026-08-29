@extends('layouts.public')

@php
    $typeLabels = ['article' => 'مقال', 'khatira' => 'خاطرة', 'fawaid' => 'فائدة', 'video' => 'فيديو يوتيوب', 'pdf' => 'ملف PDF'];
    $seo = $item->meta['seo'] ?? [];
    $title = ($seo['title'] ?? $item->title) . ' - ' . config('app.name');
    $metaDescription = $seo['description'] ?? $item->excerpt;
    $attachment = $item->attachment();
    $videoId = $item->meta['video_id'] ?? null;
    $trail = [['الرئيسية', route('home')], ['قرآنيات', route('quraniyat.index')], [$item->title, null]];
    $ogImage = $item->coverImage()?->url();
    $isArticleType = in_array($item->type, ['article', 'khatira', 'fawaid'], true);
    $ogType = $isArticleType ? 'article' : 'website';
@endphp

@if($isArticleType)
    @push('json-ld')
    <script type="application/ld+json">{!! json_encode(array_filter([
        '@@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $item->title,
        'url' => route('quraniyat.show', $item->slug),
        'description' => $item->excerpt,
        'image' => $ogImage,
        'datePublished' => $item->published_at?->toIso8601String(),
        'dateModified' => $item->updated_at?->toIso8601String(),
        'inLanguage' => 'ar',
    ]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
    @endpush
@endif

@section('public-content')
<main class="mx-auto max-w-3xl px-5 py-10 sm:py-12">
    @include('partials.breadcrumbs')

    <div class="relative mt-6 overflow-hidden rounded-3xl border border-emerald-700/10 bg-gradient-to-br from-white to-amber-50 shadow-md">
        @if($cover = $item->coverImage())
            <div class="aspect-[3/1] overflow-hidden">
                <img src="{{ $cover->displayUrl() }}" alt="{{ $item->title }}" class="h-full w-full object-cover">
            </div>
        @endif
        <div class="relative p-6 sm:p-7">
            <span class="absolute inset-x-8 top-0 h-px bg-gradient-to-l from-amber-400/70 via-amber-300/70 to-transparent"></span>
            <div class="text-center sm:text-right">
                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 ring-1 ring-emerald-100">✦ {{ $typeLabels[$item->type] ?? $item->type }}</span>
                <h1 class="mt-3 text-2xl font-extrabold sm:text-3xl">{{ $item->title }}</h1>
                @include('partials.card-divider', ['accent' => 'amber'])
                <div class="flex flex-wrap items-center justify-center gap-3 text-sm text-slate-500 sm:justify-start">
                    @if($item->published_at)<span>{{ $item->published_at->translatedFormat('j F Y') }}</span>@endif
                    @if($item->categories->isNotEmpty())<span>{{ $item->categories->pluck('name')->join('، ') }}</span>@endif
                </div>
            </div>
        </div>

        @if($item->type === 'video' && $videoId)
            <div class="px-6 pb-6 sm:px-7 sm:pb-7">
                @include('partials.youtube-embed', ['videoId' => $videoId, 'class' => 'aspect-video overflow-hidden rounded-2xl bg-black'])
            </div>
        @elseif($item->type === 'pdf' && $attachment)
            <div class="px-6 pb-6 sm:px-7 sm:pb-7">
                <a href="{{ $attachment->pdfUrl() }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-lg bg-emerald-800 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">تحميل ملف PDF</a>
            </div>
        @endif

        @if($item->body)
            @include('partials.section-divider', ['accent' => 'amber'])
            <div class="p-6 sm:p-8">
                <div class="prose prose-slate mx-auto max-w-2xl text-[17px] leading-9 text-slate-700">
                    @include('partials.rich-text', ['text' => $item->body, 'accent' => 'amber'])
                </div>
            </div>
        @endif
    </div>

    @if($item->tags->where('is_active', true)->isNotEmpty())
        <div class="mt-8 flex flex-wrap gap-2">
            @foreach($item->tags->where('is_active', true) as $tag)<span class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-600">#{{ $tag->name }}</span>@endforeach
        </div>
    @endif

    @include('partials.content-navigation', ['prev' => $prev ?? null, 'next' => $next ?? null])
</main>
@endsection
