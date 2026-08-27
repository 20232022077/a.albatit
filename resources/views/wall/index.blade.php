@extends('layouts.public')

@php
    $title = 'الحائط - ' . config('app.name');
    $metaDescription = $siteSettings->wallSubtitle();
    $wallIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 5.5h15v10h-9L6 20v-4.5h-1.5v-10Z"/></svg>';
@endphp

@section('public-content')
<main>
    @include('partials.page-hero', ['heroIcon' => $wallIcon, 'heroTitle' => 'الحائط', 'heroDescription' => $metaDescription])

    <div class="mx-auto max-w-6xl px-5 py-12 lg:px-8">
        <form action="{{ route('wall.index') }}" class="flex gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <input name="q" value="{{ $query }}" aria-label="ابحث في المنشورات" placeholder="ابحث في المنشورات" class="min-w-0 flex-1 rounded-lg border-slate-300">
            <button class="rounded-lg bg-emerald-800 px-5 py-2 font-semibold text-white transition hover:bg-emerald-700">بحث</button>
        </form>

        <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @forelse($items as $item)
                @php($isPinned = (bool) $item->wallPost?->is_pinned)
                <a href="{{ route('wall.show', $item->slug) }}" class="group flex flex-col overflow-hidden rounded-2xl border border-emerald-700/10 bg-gradient-to-br from-white via-white to-emerald-100/60 shadow-md transition-all duration-300 hover:-translate-y-2 hover:border-amber-300/40 hover:shadow-2xl {{ $isPinned ? 'ring-1 ring-amber-200 lg:col-span-2' : '' }}">
                    <div class="border-b border-slate-100 px-5 py-3.5">
                        <div class="flex flex-wrap items-center gap-2">
                            @if($isPinned)<span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-800">📌 مثبّت</span>@endif
                            <p class="text-xs text-slate-400">{{ $item->published_at?->translatedFormat('j F Y') }}</p>
                        </div>
                        <h2 class="mt-1 line-clamp-2 font-extrabold leading-7 text-slate-800 {{ $isPinned ? 'text-xl' : '' }}">{{ $item->title }}</h2>
                        @include('partials.card-divider', ['accent' => 'emerald'])
                    </div>
                    <div class="flex flex-1 flex-col px-5 py-4">
                        <p class="{{ $isPinned ? 'line-clamp-4' : 'line-clamp-3' }} flex-1 text-sm leading-7 text-slate-600">{{ \Illuminate\Support\Str::limit($item->body, $isPinned ? 220 : 140) }}</p>
                        <span class="mt-3 inline-flex items-center gap-1.5 text-sm font-bold text-emerald-700 transition group-hover:gap-2.5">
                            قراءة المزيد
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 17l-5-5 5-5M5 12h14"/></svg>
                        </span>
                    </div>
                </a>
            @empty
                <p class="col-span-full rounded-2xl border border-dashed border-slate-300 p-10 text-center text-slate-500">لا توجد منشورات حاليًا.</p>
            @endforelse
        </div>
        <div class="mt-8">{{ $items->links() }}</div>
    </div>
</main>
@endsection
