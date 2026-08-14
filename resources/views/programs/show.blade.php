@extends('layouts.public')

@php
    $seo = $item->meta['seo'] ?? [];
    $title = ($seo['title'] ?? $item->title) . ' - ' . config('app.name');
    $metaDescription = $seo['description'] ?? $item->excerpt;
    $trail = [['الرئيسية', route('home')], ['البرامج', route('programs.index')], [$item->title, null]];
    $ogImage = $item->coverImage()?->url();
@endphp

@section('public-content')
<main class="mx-auto max-w-3xl px-5 py-12">
    @include('partials.breadcrumbs')

    <h1 class="mt-4 text-3xl font-bold">{{ $item->title }}</h1>
    <div class="mt-3 flex flex-wrap items-center gap-x-6 gap-y-1 text-sm text-slate-500">
        @if($item->program?->presenter)<span>المقدّم: {{ $item->program->presenter }}</span>@endif
        @if($item->program?->started_on)<span>بدأ: {{ $item->program->started_on->translatedFormat('j F Y') }}</span>@endif
        @if($item->categories->isNotEmpty())<span>{{ $item->categories->pluck('name')->join('، ') }}</span>@endif
    </div>

    @if($cover = $item->coverImage())
        <img src="{{ $cover->displayUrl() }}" alt="{{ $item->title }}" class="mt-6 w-full rounded-2xl object-cover">
    @endif

    @if($item->excerpt)<p class="mt-6 text-lg leading-8 text-slate-700">{{ $item->excerpt }}</p>@endif
    @if($item->body)<div class="prose prose-slate mt-6 max-w-none leading-8">{!! nl2br(e($item->body)) !!}</div>@endif

    @if($item->tags->where('is_active', true)->isNotEmpty())
        <div class="mt-8 flex flex-wrap gap-2">
            @foreach($item->tags->where('is_active', true) as $tag)<span class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-600">#{{ $tag->name }}</span>@endforeach
        </div>
    @endif

    <section class="mt-12">
        <h2 class="text-xl font-bold">الحلقات ({{ $episodes->count() }})</h2>
        <div class="mt-5 space-y-8">
            @forelse($episodes as $episode)
                @php($episodeVideoId = $episode->meta['video_id'] ?? null)
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-center gap-2 text-xs font-semibold text-emerald-700">
                        <span class="rounded-full bg-emerald-50 px-2.5 py-1">الحلقة {{ $episode->programEpisode?->episode_number }}</span>
                        @if($episode->published_at)<span class="text-slate-400">{{ $episode->published_at->translatedFormat('j F Y') }}</span>@endif
                    </div>
                    <h3 class="mt-2 text-lg font-bold">{{ $episode->title }}</h3>
                    @if($episodeVideoId)
                        @include('partials.youtube-embed', ['videoId' => $episodeVideoId, 'class' => 'mt-4 aspect-video overflow-hidden rounded-xl bg-black'])
                    @elseif($episodeCover = $episode->coverImage())
                        <img src="{{ $episodeCover->url() }}" alt="{{ $episode->title }}" class="mt-4 w-full rounded-xl object-cover">
                    @endif
                    @if($episode->excerpt)<p class="mt-3 text-sm leading-6 text-slate-600">{{ $episode->excerpt }}</p>@endif
                </article>
            @empty
                <p class="rounded-xl bg-slate-100 p-6 text-center text-slate-500">لا توجد حلقات منشورة بعد.</p>
            @endforelse
        </div>
    </section>
</main>
@endsection
