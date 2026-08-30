@extends('layouts.public')

@php
    $seo = $item->meta['seo'] ?? [];
    $title = ($seo['title'] ?? $item->title) . ' - ' . config('app.name');
    $metaDescription = $seo['description'] ?? $item->excerpt;
    $reflection = $item->reflection;
    $trail = [['الرئيسية', route('home')], ['تأملات', route('reflections.index')], [$item->title, null]];
    $ogImage = $item->coverImage()?->url();
    $ogType = 'article';
@endphp

@push('json-ld')
<script type="application/ld+json">{!! json_encode(array_filter([
    '@@context' => 'https://schema.org',
    '@type' => 'Article',
    'headline' => $item->title,
    'url' => route('reflections.show', $item->slug),
    'description' => $item->excerpt,
    'image' => $ogImage,
    'datePublished' => $item->published_at?->toIso8601String(),
    'dateModified' => $item->updated_at?->toIso8601String(),
    'inLanguage' => 'ar',
]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
@endpush

@section('public-content')
<main class="mx-auto max-w-2xl px-5 py-10 sm:py-12">
    @include('partials.breadcrumbs')

    <div class="mt-6 overflow-hidden rounded-3xl border border-emerald-700/10 bg-gradient-to-br from-white to-amber-50 shadow-md">
        @if($cover = $item->coverImage())
            <div class="aspect-[3/1] overflow-hidden">
                <img src="{{ $cover->displayUrl() }}" alt="{{ $item->title }}" class="h-full w-full object-cover">
            </div>
        @endif
        <div class="p-7 text-center sm:p-10">
            @unless($cover)
                <span class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-gradient-to-br from-emerald-900 to-emerald-950 text-2xl text-amber-300 shadow-sm">۞</span>
            @endunless

            <h1 class="{{ $cover ? '' : 'mt-5' }} text-2xl font-extrabold leading-9 sm:text-3xl">{{ $item->title }}</h1>
            @include('partials.card-divider', ['accent' => 'amber'])

            <div class="flex flex-wrap items-center justify-center gap-x-5 gap-y-1 text-sm text-slate-500">
                @if($item->published_at)<span>{{ $item->published_at->translatedFormat('j F Y') }}</span>@endif
                @if($reflection?->surah_number)
                    <span class="font-semibold text-emerald-700">
                        سورة {{ $reflection->surah_number }}
                        @if($reflection->ayah_from)
                            — الآية {{ $reflection->ayah_from }}@if($reflection->ayah_to && $reflection->ayah_to !== $reflection->ayah_from) إلى {{ $reflection->ayah_to }}@endif
                        @endif
                    </span>
                @endif
                @if($item->categories->isNotEmpty())<span>{{ $item->categories->pluck('name')->join('، ') }}</span>@endif
                @include('partials.share-button', ['inline' => true, 'shareTitle' => $item->title, 'shareUrl' => route('reflections.show', $item->slug)])
            </div>
        </div>

        @if($item->body)
            @include('partials.section-divider', ['accent' => 'amber'])
            <div class="p-7 sm:p-9">
                <div class="prose prose-slate mx-auto max-w-2xl text-lg leading-9 text-slate-700">
                    @include('partials.rich-text', ['text' => $item->body, 'accent' => 'amber'])
                </div>
            </div>
        @endif
    </div>

    @if($item->tags->where('is_active', true)->isNotEmpty())
        <div class="mt-10 flex flex-wrap justify-center gap-2">
            @foreach($item->tags->where('is_active', true) as $tag)<span class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-600">#{{ $tag->name }}</span>@endforeach
        </div>
    @endif

    @include('partials.content-navigation', ['prev' => $prev ?? null, 'next' => $next ?? null])
</main>
@endsection
