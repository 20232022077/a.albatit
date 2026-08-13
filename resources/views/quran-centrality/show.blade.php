@extends('layouts.public')

@php
    $typeLabels = ['article' => 'مقال', 'study' => 'دراسة', 'video' => 'فيديو', 'pdf' => 'ملف PDF', 'image' => 'صورة توضيحية'];
    $seo = $item->meta['seo'] ?? [];
    $title = ($seo['title'] ?? $item->title) . ' - ' . config('app.name');
    $metaDescription = $seo['description'] ?? $item->excerpt;
    $attachment = $item->attachment();
@endphp

@section('public-content')
<main class="mx-auto max-w-3xl px-5 py-12">
    <a href="{{ route('quran-centrality.index') }}" class="text-sm font-semibold text-emerald-700">← مركزية القرآن</a>

    <span class="mt-6 inline-block rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">{{ $typeLabels[$item->type] ?? $item->type }}</span>
    <h1 class="mt-3 text-3xl font-bold">{{ $item->title }}</h1>
    <div class="mt-3 flex flex-wrap items-center gap-3 text-sm text-slate-500">
        @if($item->published_at)<span>{{ $item->published_at->translatedFormat('j F Y') }}</span>@endif
        @if($item->categories->isNotEmpty())<span>{{ $item->categories->pluck('name')->join('، ') }}</span>@endif
    </div>

    @if($cover = $item->coverImage())
        <img src="{{ $cover->url() }}" alt="{{ $item->title }}" class="mt-6 w-full rounded-2xl object-cover">
    @endif

    @if($item->excerpt)<p class="mt-6 text-lg leading-8 text-slate-700">{{ $item->excerpt }}</p>@endif

    @if($item->type === 'video')
        <div class="mt-6 space-y-3">
            @if($attachment && str_starts_with($attachment->mime_type, 'video'))
                <video controls class="w-full rounded-xl bg-black"><source src="{{ $attachment->url() }}" type="{{ $attachment->mime_type }}"></video>
            @endif
            @if($videoUrl = $item->meta['video_url'] ?? null)
                <a href="{{ $videoUrl }}" target="_blank" rel="noopener" class="inline-flex rounded-lg bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white">مشاهدة الفيديو</a>
            @endif
        </div>
    @elseif($item->type === 'pdf' && $attachment)
        <a href="{{ $attachment->pdfUrl() }}" target="_blank" rel="noopener" class="mt-6 inline-flex rounded-lg bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white">تحميل ملف PDF</a>
    @endif

    @if($item->body)<div class="prose prose-slate mt-8 max-w-none leading-8">{!! nl2br(e($item->body)) !!}</div>@endif

    @if($item->tags->where('is_active', true)->isNotEmpty())
        <div class="mt-8 flex flex-wrap gap-2">
            @foreach($item->tags->where('is_active', true) as $tag)<span class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-600">#{{ $tag->name }}</span>@endforeach
        </div>
    @endif
</main>
@endsection
