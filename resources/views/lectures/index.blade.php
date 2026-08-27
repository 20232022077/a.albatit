@extends('layouts.public')

@php
    $title = 'المحاضرات - ' . config('app.name');
    $metaDescription = $siteSettings->lecturesSubtitle();
    $sortLabels = ['newest' => 'الأحدث', 'oldest' => 'الأقدم', 'title' => 'العنوان (أ-ي)'];
    $lectureIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="2.5" width="6" height="12" rx="3"/><path d="M5.5 10.5a6.5 6.5 0 0 0 13 0M12 19v2.5M8.5 22h7"/></svg>';
@endphp

@section('public-content')
<main>
    @include('partials.page-hero', ['heroIcon' => $lectureIcon, 'heroTitle' => 'المحاضرات', 'heroDescription' => $metaDescription])

    <div class="mx-auto max-w-6xl px-5 py-12 lg:px-8">
        <form action="{{ route('lectures.index') }}" class="flex flex-wrap gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <input name="q" value="{{ $query }}" aria-label="ابحث عن محاضرة" placeholder="ابحث عن محاضرة" class="min-w-[220px] flex-1 rounded-lg border-slate-300">
            <select name="category_id" aria-label="تصفية حسب التصنيف" class="rounded-lg border-slate-300" data-autosubmit>
                <option value="">كل التصنيفات</option>
                @foreach($categories as $category)<option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>@endforeach
            </select>
            <select name="sort" aria-label="ترتيب حسب" class="rounded-lg border-slate-300" data-autosubmit>
                @foreach($sortLabels as $key => $label)<option value="{{ $key }}" @selected($sortKey === $key)>{{ $label }}</option>@endforeach
            </select>
            <button class="rounded-lg bg-emerald-800 px-5 py-2 font-semibold text-white transition hover:bg-emerald-700">بحث</button>
        </form>

        <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @forelse($items as $item)
                <a href="{{ route('lectures.show', $item->slug) }}" class="group flex flex-col overflow-hidden rounded-2xl border border-emerald-700/10 bg-gradient-to-br from-white to-emerald-50 shadow-md transition-all duration-300 hover:-translate-y-2 hover:border-amber-300/40 hover:shadow-2xl">
                    <div class="aspect-video overflow-hidden bg-slate-100">
                        @if($cover = $item->coverImage())
                            <img loading="lazy" src="{{ $cover->displayUrl() }}" alt="{{ $item->title }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                        @else
                            <div class="grid h-full w-full place-items-center text-slate-300">
                                <svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="2.5" width="6" height="12" rx="3"/><path d="M5.5 10.5a6.5 6.5 0 0 0 13 0M12 19v2.5M8.5 22h7"/></svg>
                            </div>
                        @endif
                    </div>
                    <div class="flex flex-1 flex-col p-5">
                        <span class="inline-flex w-fit items-center rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-bold tracking-wide text-emerald-700 ring-1 ring-emerald-100">محاضرة</span>
                        <h2 class="mt-2 text-lg font-bold leading-7">{{ $item->title }}</h2>
                        <span class="mt-1.5 block h-[3px] w-8 rounded-full bg-gradient-to-l from-amber-400 via-amber-300 to-amber-500"></span>
                        @if($item->excerpt)<p class="mt-1.5 line-clamp-2 flex-1 text-sm leading-6 text-slate-600">{{ $item->excerpt }}</p>@endif
                        @if($item->lecture?->speaker)
                            <div class="mt-3 flex items-center gap-1.5 border-t border-slate-100 pt-3 text-xs font-semibold text-slate-500">
                                <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.4"/><path d="M5 20c1-3.6 4-5.6 7-5.6s6 2 7 5.6"/></svg>
                                <span class="truncate">{{ $item->lecture->speaker }}</span>
                            </div>
                        @endif
                    </div>
                </a>
            @empty
                <p class="col-span-full rounded-2xl border border-dashed border-slate-300 p-10 text-center text-slate-500">لا توجد محاضرات منشورة حاليًا.</p>
            @endforelse
        </div>
        <div class="mt-8">{{ $items->links() }}</div>
    </div>
</main>
@endsection
