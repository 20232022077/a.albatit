@extends('layouts.public')

@php
    $seo = $item->meta['seo'] ?? [];
    $title = ($seo['title'] ?? $item->title) . ' - ' . config('app.name');
    $metaDescription = $seo['description'] ?? $item->excerpt;
    $book = $item->book;
@endphp

@section('public-content')
<main class="mx-auto max-w-4xl px-5 py-12">
    <a href="{{ route('books.index') }}" class="text-sm font-semibold text-emerald-700">← الكتب</a>

    <div class="mt-6 grid gap-8 sm:grid-cols-3">
        <div class="sm:col-span-1">
            @if($book?->cover)
                <img src="{{ $book->cover->url() }}" alt="{{ $item->title }}" class="w-full rounded-2xl object-cover shadow-sm">
            @else
                <div class="grid aspect-[3/4] w-full place-items-center rounded-2xl bg-emerald-50 text-5xl text-emerald-700">📖</div>
            @endif
            @if($book?->pdf)
                <a href="{{ $book->pdf->pdfUrl() }}" target="_blank" rel="noopener" class="mt-4 flex items-center justify-center rounded-lg bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white">تحميل الكتاب PDF</a>
            @endif
        </div>
        <div class="sm:col-span-2">
            <h1 class="text-3xl font-bold">{{ $item->title }}</h1>
            @if($book?->author_name)<p class="mt-2 text-lg text-emerald-700">{{ $book->author_name }}</p>@endif

            <div class="mt-4 flex flex-wrap gap-x-6 gap-y-1 text-sm text-slate-500">
                @if($item->published_at)<span>تاريخ النشر: {{ $item->published_at->translatedFormat('j F Y') }}</span>@endif
                @if($book?->publisher)<span>الناشر: {{ $book->publisher }}</span>@endif
                @if($book?->publication_year)<span>سنة النشر: {{ $book->publication_year }}</span>@endif
                @if($book?->pages_count)<span>عدد الصفحات: {{ $book->pages_count }}</span>@endif
            </div>

            @if($item->categories->isNotEmpty())
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach($item->categories as $category)<span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">{{ $category->name }}</span>@endforeach
                </div>
            @endif

            @if($item->excerpt)<p class="mt-6 text-lg leading-8 text-slate-700">{{ $item->excerpt }}</p>@endif
            @if($item->body)<div class="prose prose-slate mt-6 max-w-none leading-8">{!! nl2br(e($item->body)) !!}</div>@endif

            @if($item->tags->where('is_active', true)->isNotEmpty())
                <div class="mt-8 flex flex-wrap gap-2">
                    @foreach($item->tags->where('is_active', true) as $tag)<span class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-600">#{{ $tag->name }}</span>@endforeach
                </div>
            @endif
        </div>
    </div>
</main>
@endsection
