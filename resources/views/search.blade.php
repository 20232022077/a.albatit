@extends('layouts.public')

@php
    $title = ($query ? "نتائج البحث عن: {$query}" : 'نتائج البحث') . ' - ' . config('app.name');
    $robots = 'noindex,follow';
    $canonicalUrl = route('search');
@endphp

@section('public-content')
<main class="mx-auto max-w-5xl px-5 py-12">
    <h1 class="text-3xl font-bold">نتائج البحث</h1>
    <form action="{{ route('search') }}" class="mt-6 flex rounded-xl border border-slate-300 bg-white p-1 focus-within:ring-2 focus-within:ring-emerald-600 focus-within:ring-offset-1">
        <input name="q" value="{{ $query }}" class="min-w-0 flex-1 border-0 px-4 focus:ring-0" placeholder="ابحث في المحتوى">
        <button class="rounded-lg bg-emerald-700 px-5 py-2 text-white">بحث</button>
    </form>
    <p class="mt-5 text-sm text-slate-500">{{ $query ? "نتائج البحث عن: {$query}" : 'أدخل عبارة للبحث.' }}</p>

    <div class="mt-6 grid gap-5 sm:grid-cols-2">
        @forelse($items as $item)
            <a href="{{ $item->url }}" class="flex gap-4 overflow-hidden rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                @if($item->imageUrl)
                    <img loading="lazy" src="{{ $item->imageUrl }}" alt="{{ $item->title }}" class="h-24 w-24 shrink-0 rounded-lg object-cover">
                @else
                    <div class="grid h-24 w-24 shrink-0 place-items-center rounded-lg bg-emerald-50 text-2xl text-emerald-700">📄</div>
                @endif
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">{{ $item->typeLabel }}</span>
                        @if($item->publishedAt)<span class="text-xs text-slate-400">{{ $item->publishedAt->translatedFormat('j F Y') }}</span>@endif
                    </div>
                    <h2 class="mt-1.5 truncate text-lg font-bold text-slate-900">{{ $item->title }}</h2>
                    @if($item->excerpt)<p class="mt-1 line-clamp-2 text-sm leading-6 text-slate-600">{{ $item->excerpt }}</p>@endif
                </div>
            </a>
        @empty
            <p class="col-span-full rounded-xl bg-slate-100 p-6 text-center text-slate-500">لا توجد نتائج مطابقة.</p>
        @endforelse
    </div>
    <div class="mt-7">{{ $items->links() }}</div>
</main>
@endsection
