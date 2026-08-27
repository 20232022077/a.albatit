@extends('layouts.public')

@php
    $typeLabels = ['article' => 'مقال', 'study' => 'دراسة', 'video' => 'فيديو', 'pdf' => 'ملف PDF', 'image' => 'صورة توضيحية'];
    $seo = $item->meta['seo'] ?? [];
    $title = ($seo['title'] ?? $item->title) . ' - ' . config('app.name');
    $metaDescription = $seo['description'] ?? $item->excerpt;
    $attachment = $item->attachment();
    $videoId = $item->meta['video_id'] ?? null;
    $trail = [['الرئيسية', route('home')], ['مركزية القرآن', route('quran-centrality.index')], [$item->title, null]];
    $ogImage = $item->coverImage()?->url();
    $isArticleType = in_array($item->type, ['article', 'study'], true);
    $ogType = $isArticleType ? 'article' : 'website';
@endphp

@if($isArticleType)
    @push('json-ld')
    <script type="application/ld+json">{!! json_encode(array_filter([
        '@@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $item->title,
        'url' => route('quran-centrality.show', $item->slug),
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

    <div class="mt-6 overflow-hidden rounded-3xl border border-emerald-700/10 bg-gradient-to-br from-white to-emerald-50 shadow-md">
        <div class="flex flex-col gap-6 p-6 sm:flex-row sm:p-7">
            <div class="mx-auto w-40 shrink-0 sm:mx-0">
                @if($cover = $item->coverImage())
                    <img src="{{ $cover->displayUrl() }}" alt="{{ $item->title }}" class="aspect-square w-full rounded-2xl object-cover">
                @else
                    <div class="grid aspect-square w-full place-items-center rounded-2xl bg-gradient-to-br from-emerald-50 to-emerald-100 text-emerald-700">
                        <svg class="h-12 w-12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3"><circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="1.6"/><path d="M12 4v2.4M12 17.6V20M4 12h2.4M17.6 12H20"/></svg>
                    </div>
                @endif
            </div>
            <div class="min-w-0 flex-1 text-center sm:text-right">
                <span class="inline-flex w-fit items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 ring-1 ring-emerald-100">{{ $typeLabels[$item->type] ?? $item->type }}</span>
                <h1 class="mt-3 text-2xl font-extrabold sm:text-3xl">{{ $item->title }}</h1>
                @include('partials.card-divider', ['accent' => 'slate'])
                <div class="flex flex-wrap items-center justify-center gap-3 text-sm text-slate-500 sm:justify-start">
                    @if($item->published_at)<span>{{ $item->published_at->translatedFormat('j F Y') }}</span>@endif
                    @if($item->categories->isNotEmpty())<span>{{ $item->categories->pluck('name')->join('، ') }}</span>@endif
                </div>
            </div>
        </div>

        @if($item->type === 'video')
            <div class="space-y-3 px-6 pb-6 sm:px-7 sm:pb-7">
                @if($attachment && str_starts_with($attachment->mime_type, 'video'))
                    <video controls class="w-full rounded-2xl bg-black"><source src="{{ $attachment->url() }}" type="{{ $attachment->mime_type }}"></video>
                @endif
                @if($videoId)
                    @include('partials.youtube-embed', ['videoId' => $videoId, 'class' => 'aspect-video overflow-hidden rounded-2xl bg-black'])
                @endif
            </div>
        @elseif($item->type === 'pdf' && $attachment)
            <div class="px-6 pb-6 sm:px-7 sm:pb-7">
                <a href="{{ $attachment->pdfUrl() }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-lg bg-emerald-800 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">تحميل ملف PDF</a>
            </div>
        @endif

        @if($item->body)
            @include('partials.section-divider', ['accent' => 'slate'])
            <div class="p-6 sm:p-8">
                <div class="prose prose-slate mx-auto max-w-2xl text-[17px] leading-9 text-slate-700">
                    @include('partials.rich-text', ['text' => $item->body])
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
