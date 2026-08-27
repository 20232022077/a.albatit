@extends('layouts.public')

@php
    $title = \Illuminate\Support\Str::limit($item->title, 60) . ' - ' . config('app.name');
    $metaDescription = \Illuminate\Support\Str::limit($item->body, 160);
    $videoId = $item->meta['video_id'] ?? null;
    $trail = [['الرئيسية', route('home')], ['الحائط', route('wall.index')], [\Illuminate\Support\Str::limit($item->title, 40), null]];
    $ogImage = $item->coverImage()?->url();
@endphp

@section('public-content')
<main class="mx-auto max-w-2xl px-5 py-10 sm:py-12">
    @include('partials.breadcrumbs')

    <article class="mt-6 overflow-hidden rounded-3xl border border-emerald-700/10 bg-gradient-to-br from-white to-emerald-50 shadow-md">
        <header class="border-b border-slate-100 px-6 py-5 sm:px-8">
            <div class="flex flex-wrap items-center gap-2">
                @if($item->wallPost?->is_pinned)<span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-800">📌 مثبّت</span>@endif
                <p class="text-xs text-slate-400">{{ $item->published_at?->translatedFormat('j F Y') }}</p>
            </div>
            <h1 class="mt-1.5 text-xl font-extrabold leading-8 text-slate-800 sm:text-2xl">{{ $item->title }}</h1>
            @include('partials.card-divider', ['accent' => 'emerald'])
        </header>

        <div class="px-6 py-6 sm:px-8">
            <div class="prose prose-slate max-w-none text-[17px] leading-9 text-slate-700">
                @include('partials.rich-text', ['text' => $item->body])
            </div>

            @if($cover = $item->coverImage())
                <img loading="lazy" src="{{ $cover->displayUrl() }}" alt="{{ $item->title }}" class="mt-6 w-full rounded-2xl object-cover">
            @endif
            @if($videoId)
                @include('partials.youtube-embed', ['videoId' => $videoId, 'class' => 'mt-6 aspect-video overflow-hidden rounded-2xl bg-black'])
            @endif
        </div>
    </article>

    @include('partials.content-navigation', ['prev' => $prev ?? null, 'next' => $next ?? null])
</main>
@endsection
