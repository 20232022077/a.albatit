@extends('layouts.public')

@php
    $title = 'التأملات - ' . config('app.name');
    $metaDescription = $siteSettings->reflectionsSubtitle();
    $sortLabels = ['newest' => 'الأحدث', 'oldest' => 'الأقدم', 'title' => 'العنوان (أ-ي)'];
    $reflectionIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18.5h6M10 21.5h4M12 3a6.2 6.2 0 0 0-3 11.6c.6.4 1 1.1 1 1.9h4c0-.8.4-1.5 1-1.9A6.2 6.2 0 0 0 12 3Z"/></svg>';
@endphp

@section('public-content')
<main>
    @include('partials.page-hero', ['heroIcon' => $reflectionIcon, 'heroTitle' => 'التأملات', 'heroDescription' => $metaDescription])

    <div class="mx-auto max-w-5xl px-5 py-12 lg:px-8">
        <form action="{{ route('reflections.index') }}" class="flex flex-wrap gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <input name="q" value="{{ $query }}" aria-label="ابحث في التأملات" placeholder="ابحث في التأملات" class="min-w-[220px] flex-1 rounded-lg border-slate-300">
            <select name="category_id" aria-label="تصفية حسب التصنيف" class="rounded-lg border-slate-300" data-autosubmit>
                <option value="">كل التصنيفات</option>
                @foreach($categories as $category)<option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>@endforeach
            </select>
            <select name="sort" aria-label="ترتيب حسب" class="rounded-lg border-slate-300" data-autosubmit>
                @foreach($sortLabels as $key => $label)<option value="{{ $key }}" @selected($sortKey === $key)>{{ $label }}</option>@endforeach
            </select>
            <button class="rounded-lg bg-emerald-800 px-5 py-2 font-semibold text-white transition hover:bg-emerald-700">بحث</button>
        </form>

        <div class="mt-8 grid gap-6 sm:grid-cols-2">
            @forelse($items as $item)
                <a href="{{ route('reflections.show', $item->slug) }}" class="group relative block overflow-hidden rounded-2xl border border-emerald-700/10 bg-gradient-to-br from-white to-amber-50 shadow-md transition-all duration-300 hover:-translate-y-2 hover:border-amber-300/40 hover:shadow-2xl">
                    @if($cover = $item->coverImage())
                        <div class="aspect-[3/1] overflow-hidden">
                            <img loading="lazy" src="{{ $cover->displayUrl() }}" alt="{{ $item->title }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                        </div>
                    @endif
                    <div class="p-7">
                        <span class="text-3xl text-amber-300/80" aria-hidden="true">”</span>
                        <div class="-mt-2 flex items-start justify-between gap-1.5">
                            <h2 class="min-w-0 flex-1 text-xl font-bold leading-8">{{ $item->title }}</h2>
                            @include('partials.share-button', ['inline' => true, 'shareTitle' => $item->title, 'shareUrl' => route('reflections.show', $item->slug)])
                        </div>
                        @include('partials.card-divider', ['accent' => 'amber'])
                        <p class="line-clamp-3 leading-8 text-slate-600">{{ \Illuminate\Support\Str::limit((string) $item->body, 160) }}</p>
                        <div class="mt-4 flex items-center justify-between gap-2">
                            <span class="text-xs font-semibold text-slate-500">{{ $item->published_at?->translatedFormat('j F Y') }}</span>
                            <span class="inline-flex items-center gap-1 text-sm font-bold text-emerald-700 transition group-hover:gap-2">
                                قراءة المزيد
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 17l-5-5 5-5M5 12h14"/></svg>
                            </span>
                        </div>
                    </div>
                </a>
            @empty
                <p class="col-span-full rounded-2xl border border-dashed border-slate-300 p-10 text-center text-slate-500">لا توجد تأملات منشورة حاليًا.</p>
            @endforelse
        </div>
        <div class="mt-8">{{ $items->links() }}</div>
    </div>
</main>
@endsection
