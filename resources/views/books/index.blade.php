@extends('layouts.public')

@php
    $title = 'الكتب - ' . config('app.name');
    $metaDescription = $siteSettings->booksSubtitle();
    $sortLabels = ['newest' => 'الأحدث', 'oldest' => 'الأقدم', 'title' => 'العنوان (أ-ي)'];
    $bookIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="4.5" y="3" width="15" height="18" rx="2"/><path d="M8.5 7.5h7M8.5 11.5h7M8.5 15.5h4.5"/></svg>';
@endphp

@section('public-content')
<main>
    @include('partials.page-hero', ['heroIcon' => $bookIcon, 'heroTitle' => 'الكتب', 'heroDescription' => $metaDescription])

    <div class="mx-auto max-w-6xl px-5 py-12 lg:px-8">
        <form action="{{ route('books.index') }}" class="flex flex-wrap gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <input name="q" value="{{ $query }}" aria-label="ابحث عن كتاب" placeholder="ابحث عن كتاب" class="min-w-[220px] flex-1 rounded-lg border-slate-300">
            <select name="category_id" aria-label="تصفية حسب التصنيف" class="rounded-lg border-slate-300" data-autosubmit>
                <option value="">كل التصنيفات</option>
                @foreach($categories as $category)<option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>@endforeach
            </select>
            <select name="sort" aria-label="ترتيب حسب" class="rounded-lg border-slate-300" data-autosubmit>
                @foreach($sortLabels as $key => $label)<option value="{{ $key }}" @selected($sortKey === $key)>{{ $label }}</option>@endforeach
            </select>
            <button class="rounded-lg bg-emerald-800 px-5 py-2 font-semibold text-white transition hover:bg-emerald-700">بحث</button>
        </form>

        <div class="mt-8 grid gap-6 sm:grid-cols-3 lg:grid-cols-4">
            @forelse($items as $item)
                <a href="{{ route('books.show', $item->slug) }}" class="group relative flex flex-col overflow-hidden rounded-2xl border border-emerald-700/10 bg-gradient-to-br from-white to-emerald-50 shadow-md transition-all duration-300 hover:-translate-y-2 hover:border-amber-300/40 hover:shadow-2xl">
                    <span class="absolute inset-x-0 top-0 z-10 h-1 bg-gradient-to-l from-amber-400 via-amber-300 to-amber-100"></span>
                    @if($item->book?->cover)
                        <div class="aspect-[3/4] overflow-hidden">
                            <img loading="lazy" src="{{ $item->book->cover->displayUrl() }}" alt="{{ $item->title }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                        </div>
                        <div class="p-4">
                            @if($item->book?->author_name)<p class="text-[11px] font-bold tracking-wide text-amber-700">{{ $item->book->author_name }}</p>@endif
                            <h2 class="mt-1.5 line-clamp-1 font-bold">{{ $item->title }}</h2>
                            <span class="mt-1.5 block h-[3px] w-8 rounded-full bg-gradient-to-l from-amber-400 via-amber-300 to-amber-500"></span>
                            <p class="mt-1.5 line-clamp-2 text-xs leading-5 text-slate-500">{{ $item->excerpt }}</p>
                            <span class="mt-2 inline-flex items-center gap-1 text-xs font-bold text-emerald-700 transition group-hover:gap-1.5">
                                قراءة الكتاب
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 17l-5-5 5-5M5 12h14"/></svg>
                            </span>
                        </div>
                    @else
                        <div class="flex flex-1 flex-col justify-center bg-gradient-to-br from-white to-emerald-50 p-5 text-center">
                            @if($item->book?->author_name)<p class="text-[11px] font-bold tracking-wide text-amber-700">{{ $item->book->author_name }}</p>@endif
                            <h2 class="mt-2 line-clamp-3 font-bold leading-6">{{ $item->title }}</h2>
                            <span class="mx-auto mt-1.5 block h-[3px] w-8 rounded-full bg-gradient-to-l from-amber-400 via-amber-300 to-amber-500"></span>
                            <span class="mx-auto mt-2 inline-flex items-center gap-1 text-xs font-bold text-emerald-700 transition group-hover:gap-1.5">
                                قراءة الكتاب
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 17l-5-5 5-5M5 12h14"/></svg>
                            </span>
                        </div>
                    @endif
                </a>
            @empty
                <p class="col-span-full rounded-2xl border border-dashed border-slate-300 p-10 text-center text-slate-500">لا توجد كتب منشورة حاليًا.</p>
            @endforelse
        </div>
        <div class="mt-8">{{ $items->links() }}</div>
    </div>
</main>
@endsection
