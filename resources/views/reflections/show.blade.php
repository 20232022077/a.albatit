@extends('layouts.public')

@php
    $seo = $item->meta['seo'] ?? [];
    $title = ($seo['title'] ?? $item->title) . ' - ' . config('app.name');
    $metaDescription = $seo['description'] ?? $item->excerpt;
    $reflection = $item->reflection;
    $trail = [['الرئيسية', route('home')], ['تأملات', route('reflections.index')], [$item->title, null]];
    $ogImage = $item->coverImage()?->url();
@endphp

@section('public-content')
<main class="mx-auto max-w-3xl px-5 py-12">
    @include('partials.breadcrumbs')

    <h1 class="mt-4 text-3xl font-bold">{{ $item->title }}</h1>
    <div class="mt-3 flex flex-wrap items-center gap-x-6 gap-y-1 text-sm text-slate-500">
        @if($item->published_at)<span>{{ $item->published_at->translatedFormat('j F Y') }}</span>@endif
        @if($reflection?->surah_number)
            <span>
                سورة {{ $reflection->surah_number }}
                @if($reflection->ayah_from)
                    — الآية {{ $reflection->ayah_from }}@if($reflection->ayah_to && $reflection->ayah_to !== $reflection->ayah_from) إلى {{ $reflection->ayah_to }}@endif
                @endif
            </span>
        @endif
        @if($item->categories->isNotEmpty())<span>{{ $item->categories->pluck('name')->join('، ') }}</span>@endif
    </div>

    @if($cover = $item->coverImage())
        <img src="{{ $cover->url() }}" alt="{{ $item->title }}" class="mt-6 w-full rounded-2xl object-cover">
    @endif

    @if($item->excerpt)<p class="mt-6 text-lg leading-8 text-slate-700">{{ $item->excerpt }}</p>@endif
    @if($item->body)<div class="prose prose-slate mt-6 max-w-none leading-8">{!! nl2br(e($item->body)) !!}</div>@endif

    @if($item->tags->where('is_active', true)->isNotEmpty())
        <div class="mt-8 flex flex-wrap gap-2">
            @foreach($item->tags->where('is_active', true) as $tag)<span class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-600">#{{ $tag->name }}</span>@endforeach
        </div>
    @endif
</main>
@endsection
