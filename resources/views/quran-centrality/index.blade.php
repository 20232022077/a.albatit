@extends('layouts.public')

@php
    $typeLabels = ['article' => 'مقال', 'study' => 'دراسة', 'video' => 'فيديو', 'pdf' => 'ملف PDF', 'image' => 'صورة توضيحية'];
    $title = 'مركزية القرآن - ' . config('app.name');
    $metaDescription = $siteSettings->quranCentralitySubtitle();
@endphp

@section('public-content')
<main>
    @include('partials.page-hero', ['heroIcon' => '۞', 'heroTitle' => 'مركزية القرآن', 'heroDescription' => $metaDescription])

    <div class="mx-auto max-w-6xl px-5 py-12 lg:px-8">
        <form action="{{ route('quran-centrality.index') }}" class="flex flex-wrap gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <input name="q" value="{{ $query }}" aria-label="ابحث في هذا القسم" placeholder="ابحث في هذا القسم" class="min-w-[220px] flex-1 rounded-lg border-slate-300">
            <select name="type" aria-label="تصفية حسب النوع" class="rounded-lg border-slate-300" data-autosubmit>
                <option value="">كل الأنواع</option>
                @foreach($typeLabels as $key => $label)<option value="{{ $key }}" @selected($type === $key)>{{ $label }}</option>@endforeach
            </select>
            <button class="rounded-lg bg-emerald-800 px-5 py-2 font-semibold text-white transition hover:bg-emerald-700">بحث</button>
        </form>

        <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @forelse($items as $item)
                <a href="{{ route('quran-centrality.show', $item->slug) }}" class="group flex flex-col overflow-hidden rounded-2xl border border-emerald-700/10 bg-gradient-to-br from-white to-emerald-50 shadow-md transition-all duration-300 hover:-translate-y-2 hover:border-amber-300/40 hover:shadow-2xl">
                    @if($cover = $item->coverImage())
                        <div class="aspect-[4/3] overflow-hidden">
                            <img loading="lazy" src="{{ $cover->displayUrl() }}" alt="{{ $item->title }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                        </div>
                    @endif
                    <div class="flex flex-1 flex-col p-5">
                        <span class="inline-flex w-fit items-center rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-bold tracking-wide text-emerald-700 ring-1 ring-emerald-100">{{ $typeLabels[$item->type] ?? $item->type }}</span>
                        <h2 class="mt-2.5 text-lg font-bold leading-7">{{ $item->title }}</h2>
                        @include('partials.card-divider', ['accent' => 'slate'])
                        <p class="line-clamp-3 flex-1 text-sm leading-6 text-slate-600">{{ \Illuminate\Support\Str::limit((string) $item->body, 140) }}</p>
                        <span class="mt-3 inline-flex items-center gap-1 text-xs font-bold text-emerald-700 transition group-hover:gap-1.5">
                            قراءة المزيد
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 17l-5-5 5-5M5 12h14"/></svg>
                        </span>
                    </div>
                </a>
            @empty
                <p class="col-span-full rounded-2xl border border-dashed border-slate-300 p-10 text-center text-slate-500">لا يوجد محتوى منشور حاليًا في هذا القسم.</p>
            @endforelse
        </div>
        <div class="mt-8">{{ $items->links() }}</div>
    </div>
</main>
@endsection
