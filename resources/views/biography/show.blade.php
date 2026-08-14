@extends('layouts.public')

@php
    $contentItem = $biography->contentItem;
    $title = $contentItem->title . ' - ' . config('app.name');
    $metaDescription = $contentItem->excerpt;
    $socialLinks = $contentItem->meta['social_links'] ?? [];
    $socialLabels = ['twitter' => 'X (تويتر)', 'x' => 'X (تويتر)', 'youtube' => 'يوتيوب', 'facebook' => 'فيسبوك', 'instagram' => 'إنستغرام', 'telegram' => 'تيليجرام', 'snapchat' => 'سناب شات', 'tiktok' => 'تيك توك', 'website' => 'الموقع'];
    $groups = [
        'qualification' => ['label' => 'المؤهلات العلمية', 'icon' => '🎓'],
        'work' => ['label' => 'الخبرات المهنية', 'icon' => '💼'],
        'development' => ['label' => 'الأنشطة العلمية والتطويرية', 'icon' => '🔬'],
        'teaching' => ['label' => 'الأنشطة التعليمية الحالية', 'icon' => '📚'],
        'achievement' => ['label' => 'الإنجازات', 'icon' => '🏆'],
    ];
    $visibleSections = $biography->sections->where('is_visible', true);
@endphp

@section('public-content')
<section class="relative overflow-hidden bg-gradient-to-b from-emerald-950 via-emerald-900 to-emerald-950 text-white">
    <div class="pointer-events-none absolute -top-24 -left-24 h-96 w-96 rounded-full bg-emerald-500/20 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-32 -right-24 h-96 w-96 rounded-full bg-teal-400/10 blur-3xl"></div>

    <div class="relative mx-auto max-w-3xl px-5 py-24 text-center lg:px-8">
        <div class="mx-auto w-56 sm:w-64">
            <div class="relative rounded-[2rem] bg-gradient-to-br from-emerald-400 via-teal-300 to-amber-300 p-1.5 shadow-2xl">
                @if($biography->profileImage)
                    <img src="{{ $biography->profileImage->url() }}" alt="{{ $contentItem->title }}" class="aspect-square w-full rounded-[1.7rem] object-cover">
                @else
                    <div class="grid aspect-square w-full place-items-center rounded-[1.7rem] bg-emerald-800 text-6xl">👤</div>
                @endif
            </div>
        </div>

        <h1 class="mt-8 text-4xl font-extrabold tracking-tight sm:text-5xl">{{ $contentItem->title }}</h1>
        @if($contentItem->excerpt)<p class="mx-auto mt-4 max-w-xl text-lg leading-8 text-emerald-100">{{ $contentItem->excerpt }}</p>@endif

        @if(filled($socialLinks))
            <div class="mt-7 flex flex-wrap justify-center gap-3">
                @foreach($socialLinks as $key => $url)
                    @if(filled($url))
                        <a href="{{ $url }}" target="_blank" rel="noopener" class="rounded-full bg-white/10 px-4 py-2 text-sm font-semibold text-white ring-1 ring-white/25 transition hover:-translate-y-0.5 hover:bg-white/20">{{ $socialLabels[strtolower($key)] ?? $key }}</a>
                    @endif
                @endforeach
            </div>
        @endif
    </div>

    <svg class="relative block h-10 w-full text-slate-50" viewBox="0 0 1200 40" preserveAspectRatio="none" fill="currentColor"><path d="M0 40 C300 0 900 0 1200 40 Z"></path></svg>
</section>

<main class="mx-auto -mt-2 max-w-4xl px-5 pb-16 lg:px-8">
    @if($contentItem->body)
        <section class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
            <p class="text-sm font-bold text-emerald-700">نبذة</p>
            <div class="prose prose-slate mt-3 max-w-none leading-8">{!! nl2br(e($contentItem->body)) !!}</div>
        </section>
    @endif

    @if($books->isNotEmpty())
        <section class="mt-14">
            <div class="flex items-center gap-3"><span class="grid h-11 w-11 place-items-center rounded-2xl bg-emerald-50 text-xl">📖</span><h2 class="text-2xl font-extrabold">الكتب التي ألّفها</h2></div>
            <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($books as $item)
                    <a href="{{ route('books.show', $item->slug) }}" class="group block overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
                        @if($item->book?->cover)
                            <img src="{{ $item->book->cover->url() }}" alt="{{ $item->title }}" class="h-52 w-full object-cover transition duration-300 group-hover:scale-105">
                        @else
                            <div class="grid h-52 w-full place-items-center bg-emerald-50 text-4xl text-emerald-700">📖</div>
                        @endif
                        <div class="p-5">
                            <h3 class="text-lg font-bold">{{ $item->title }}</h3>
                            @if($item->excerpt)<p class="mt-2 line-clamp-2 text-sm leading-6 text-slate-600">{{ $item->excerpt }}</p>@endif
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @foreach($groups as $type => $group)
        @php $sections = $visibleSections->where('type', $type)->sortBy('sort_order'); @endphp
        @if($sections->isNotEmpty())
            <section class="mt-14">
                <div class="flex items-center gap-3"><span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-emerald-50 text-xl">{{ $group['icon'] }}</span><h2 class="text-2xl font-extrabold">{{ $group['label'] }}</h2></div>
                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    @foreach($sections as $section)
                        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                            <h3 class="font-bold text-slate-800">{{ $section->title }}</h3>
                            @if($section->body)<p class="mt-2 leading-7 text-slate-600">{{ $section->body }}</p>@endif
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    @endforeach
</main>
@endsection
