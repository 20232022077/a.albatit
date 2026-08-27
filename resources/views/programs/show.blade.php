@extends('layouts.public')

@php
    $seo = $item->meta['seo'] ?? [];
    $title = ($seo['title'] ?? $item->title) . ' - ' . config('app.name');
    $metaDescription = $seo['description'] ?? $item->excerpt;
    $trail = [['الرئيسية', route('home')], ['البرامج', route('programs.index')], [$item->title, null]];
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
                    <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M10 8.5l6 3.5-6 3.5v-7z"/></svg>
                </div>
            @endif
        </div>
        <div class="min-w-0 flex-1 text-center sm:text-right">
            <span class="inline-flex w-fit items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 ring-1 ring-emerald-100">برنامج</span>
            <h1 class="mt-2 text-2xl font-extrabold sm:text-3xl">{{ $item->title }}</h1>
            <span class="mx-auto mt-2 block h-[3px] w-12 rounded-full bg-gradient-to-l from-amber-400 via-amber-300 to-amber-500 sm:mx-0"></span>
            <div class="mt-3 flex flex-wrap items-center justify-center gap-x-5 gap-y-1 text-sm text-slate-500 sm:justify-start">
                @if($item->program?->presenter)<span>المقدّم: {{ $item->program->presenter }}</span>@endif
                @if($item->program?->started_on)<span>بدأ: {{ $item->program->started_on->translatedFormat('j F Y') }}</span>@endif
                @if($item->categories->isNotEmpty())<span>{{ $item->categories->pluck('name')->join('، ') }}</span>@endif
            </div>
            @if($item->excerpt)<p class="mt-4 leading-7 text-slate-600">{{ $item->excerpt }}</p>@endif
        </div>
    </div>

    @if($item->body)
        <div class="mt-10">
            <h2 class="text-lg font-bold text-slate-900">نبذة عن البرنامج</h2>
            <div class="mt-4 rounded-3xl border border-emerald-700/10 bg-gradient-to-br from-white to-emerald-50 p-6 shadow-md sm:p-8">
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

    <section class="mt-12">
        <div class="flex items-center gap-3">
            <h2 class="text-xl font-extrabold text-slate-900">الحلقات</h2>
            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700">{{ $episodes->count() }}</span>
        </div>
        <div class="mt-5 space-y-5">
            @forelse($episodes as $episode)
                @php($episodeVideoId = $episode->meta['video_id'] ?? null)
                <article class="overflow-hidden rounded-2xl border border-emerald-700/10 bg-gradient-to-br from-white to-emerald-50 p-5 shadow-md sm:p-6">
                    <div class="flex flex-wrap items-center gap-2 text-xs font-bold text-emerald-700">
                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 ring-1 ring-emerald-100">الحلقة {{ $episode->programEpisode?->episode_number }}</span>
                        @if($episode->published_at)<span class="font-normal text-slate-400">{{ $episode->published_at->translatedFormat('j F Y') }}</span>@endif
                    </div>
                    <h3 class="mt-2 text-lg font-bold text-slate-900">{{ $episode->title }}</h3>
                    <span class="mt-1.5 block h-[3px] w-8 rounded-full bg-gradient-to-l from-amber-400 via-amber-300 to-amber-500"></span>
                    @if($episode->excerpt)<p class="mt-2 text-sm leading-6 text-slate-600">{{ $episode->excerpt }}</p>@endif

                    @if($episodeVideoId)
                        @include('partials.youtube-embed', ['videoId' => $episodeVideoId, 'class' => 'mt-4 aspect-video overflow-hidden rounded-2xl bg-black'])
                    @elseif($episodeCover = $episode->coverImage())
                        <img src="{{ $episodeCover->displayUrl() }}" alt="{{ $episode->title }}" class="mt-4 aspect-video w-full rounded-2xl object-cover">
                    @else
                        <div class="mt-4 grid aspect-video w-full place-items-center rounded-2xl bg-emerald-50 text-3xl text-emerald-700">📺</div>
                    @endif
                </article>
            @empty
                <p class="rounded-2xl border border-dashed border-slate-300 p-8 text-center text-slate-500">لا توجد حلقات منشورة بعد.</p>
            @endforelse
        </div>
    </section>

    @include('partials.content-navigation', ['prev' => $prev ?? null, 'next' => $next ?? null])
</main>
@endsection
