@extends('layouts.public')

@php
    $title = 'الحائط - ' . config('app.name');
    $metaDescription = 'منشورات قصيرة وخواطر ومقتطفات من المنصة.';
@endphp

@section('public-content')
<main class="mx-auto max-w-2xl px-5 py-12">
    <h1 class="text-3xl font-bold">الحائط</h1>
    <p class="mt-3 leading-7 text-slate-600">{{ $metaDescription }}</p>

    <form action="{{ route('wall.index') }}" class="mt-7 flex gap-3">
        <input name="q" value="{{ $query }}" placeholder="ابحث في المنشورات" class="min-w-0 flex-1 rounded-lg border-slate-300">
        <button class="rounded-lg bg-emerald-700 px-5 py-2 text-white">بحث</button>
    </form>

    <div class="mt-8 space-y-5">
        @forelse($items as $item)
            @php
                $videoUrl = $item->meta['video_url'] ?? null;
                $embedUrl = null;
                if ($videoUrl && preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|shorts\/))([A-Za-z0-9_-]{6,})/', $videoUrl, $m)) {
                    $embedUrl = 'https://www.youtube.com/embed/' . $m[1];
                }
            @endphp
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center gap-2">
                    @if($item->wallPost?->is_pinned)<span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">📌 مثبّت</span>@endif
                    <p class="text-xs text-slate-400">{{ $item->published_at?->translatedFormat('j F Y') }}</p>
                </div>
                <p class="mt-3 whitespace-pre-line leading-8 text-slate-800">{{ $item->body }}</p>
                @if($cover = $item->coverImage())
                    <img src="{{ $cover->url() }}" alt="" class="mt-4 w-full rounded-xl object-cover">
                @endif
                @if($embedUrl)
                    <div class="mt-4 aspect-video overflow-hidden rounded-xl bg-black">
                        <iframe src="{{ $embedUrl }}" class="h-full w-full" allowfullscreen loading="lazy"></iframe>
                    </div>
                @endif
            </article>
        @empty
            <p class="rounded-xl bg-slate-100 p-8 text-center text-slate-500">لا توجد منشورات حاليًا.</p>
        @endforelse
    </div>
    <div class="mt-8">{{ $items->links() }}</div>
</main>
@endsection
