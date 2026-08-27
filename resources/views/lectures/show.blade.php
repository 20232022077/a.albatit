@extends('layouts.public')

@php
    $seo = $item->meta['seo'] ?? [];
    $title = ($seo['title'] ?? $item->title) . ' - ' . config('app.name');
    $metaDescription = $seo['description'] ?? $item->excerpt;
    $videoId = $item->meta['video_id'] ?? null;
    $trail = [['الرئيسية', route('home')], ['المحاضرات', route('lectures.index')], [$item->title, null]];
    $ogImage = $item->coverImage()?->url();
@endphp

@section('public-content')
<main class="mx-auto max-w-3xl px-5 py-10 sm:py-12">
    @include('partials.breadcrumbs')

    <div class="mt-6 flex flex-col gap-6 rounded-3xl border border-emerald-700/10 bg-gradient-to-br from-white to-emerald-50 p-6 shadow-md sm:flex-row sm:p-7">
        <div class="mx-auto w-full max-w-xs shrink-0 sm:mx-0 sm:w-56">
            @if($cover = $item->coverImage())
                <img src="{{ $cover->displayUrl() }}" alt="{{ $item->title }}" class="aspect-video w-full rounded-2xl object-cover">
            @else
                <div class="grid aspect-video w-full place-items-center rounded-2xl bg-gradient-to-br from-emerald-50 to-emerald-100 text-emerald-700">
                    <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="2.5" width="6" height="12" rx="3"/><path d="M5.5 10.5a6.5 6.5 0 0 0 13 0M12 19v2.5M8.5 22h7"/></svg>
                </div>
            @endif
        </div>
        <div class="min-w-0 flex-1 text-center sm:text-right">
            <span class="inline-flex w-fit items-center gap-1 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 ring-1 ring-emerald-100">محاضرة</span>
            <h1 class="mt-2 text-2xl font-extrabold sm:text-3xl">{{ $item->title }}</h1>
            <span class="mx-auto mt-2 block h-[3px] w-12 rounded-full bg-gradient-to-l from-amber-400 via-amber-300 to-amber-500 sm:mx-0"></span>
            <div class="mt-3 flex flex-wrap items-center justify-center gap-x-5 gap-y-1 text-sm text-slate-500 sm:justify-start">
                @if($item->lecture?->speaker)<span>المحاضر: {{ $item->lecture->speaker }}</span>@endif
                @if($item->lecture?->venue)<span>المكان: {{ $item->lecture->venue }}</span>@endif
                @if($item->lecture?->delivered_at)<span>تاريخ الإلقاء: {{ $item->lecture->delivered_at->translatedFormat('j F Y') }}</span>@endif
                @if($item->categories->isNotEmpty())<span>{{ $item->categories->pluck('name')->join('، ') }}</span>@endif
            </div>
            @if($item->excerpt)<p class="mt-4 leading-7 text-slate-600">{{ $item->excerpt }}</p>@endif
        </div>
    </div>

    @if($videoId)
        @include('partials.youtube-embed', ['videoId' => $videoId, 'class' => 'mt-8 aspect-video overflow-hidden rounded-2xl bg-black'])
    @endif

    @if($item->body)
        <div class="mt-10">
            <h2 class="text-lg font-bold text-slate-900">نبذة عن المحاضرة</h2>
            <div class="mt-4 rounded-3xl border border-emerald-700/10 bg-gradient-to-br from-white to-emerald-50 p-6 shadow-sm sm:p-8">
                <div class="prose prose-slate mx-auto max-w-2xl text-[17px] leading-9 text-slate-700">
                    @include('partials.rich-text', ['text' => $item->body])
                </div>
            </div>
        </div>
    @endif

    @if($item->tags->where('is_active', true)->isNotEmpty())
        <div class="mt-8 flex flex-wrap gap-2">
            @foreach($item->tags->where('is_active', true) as $tag)<span class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-600">#{{ $tag->name }}</span>@endforeach
        </div>
    @endif

    @include('partials.content-navigation', ['prev' => $prev ?? null, 'next' => $next ?? null])
</main>
@endsection
