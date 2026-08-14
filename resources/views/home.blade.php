@extends('layouts.public')

@php
    $typeLabels = ['book' => 'كتاب', 'lecture' => 'محاضرة', 'program' => 'برنامج', 'program_episode' => 'حلقة برنامج', 'reflection' => 'تأمل', 'wall_post' => 'حائط'];
    $title = config('app.name');
    $canonicalUrl = route('home');
@endphp

@push('json-ld')
<script type="application/ld+json">{!! json_encode([
    '@@context' => 'https://schema.org',
    '@type' => 'WebSite',
    'name' => config('app.name'),
    'url' => route('home'),
    'potentialAction' => [
        '@type' => 'SearchAction',
        'target' => route('search') . '?q={search_term_string}',
        'query-input' => 'required name=search_term_string',
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush

@section('public-content')
<main>
    <section class="relative overflow-hidden bg-gradient-to-bl from-emerald-950 via-emerald-900 to-teal-900 text-white">
        <div class="pointer-events-none absolute -top-40 -left-32 h-[28rem] w-[28rem] rounded-full bg-emerald-500/20 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-40 right-0 h-[28rem] w-[28rem] rounded-full bg-amber-400/10 blur-3xl"></div>
        <div class="pointer-events-none absolute inset-0 opacity-[0.04]" style="background-image:radial-gradient(circle,#fff 1px,transparent 1px);background-size:26px 26px"></div>

        <div class="relative mx-auto grid max-w-7xl gap-10 px-4 py-16 sm:gap-12 sm:px-5 sm:py-24 lg:grid-cols-2 lg:items-center lg:px-8 lg:py-32">
            <div class="min-w-0">
                <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-1.5 text-xs font-semibold text-emerald-100 ring-1 ring-white/20">۞ منصة معرفة وإلهام</span>
                <h1 class="mt-6 text-3xl font-extrabold leading-[1.2] tracking-tight sm:text-5xl sm:leading-[1.1] lg:text-6xl">محتوى إسلامي أصيل<br><span class="bg-gradient-to-l from-amber-300 to-emerald-300 bg-clip-text text-transparent">يقرّب العلم إلى الحياة</span></h1>
                <p class="mt-6 max-w-xl text-base leading-7 text-emerald-100/90 sm:text-lg sm:leading-8">مكتبة متكاملة للكتب والمحاضرات والبرامج والتأملات ومحتوى القرآن الكريم.</p>
                <form action="{{ route('search') }}" class="mt-8 flex max-w-lg rounded-2xl bg-white p-2 shadow-xl focus-within:ring-2 focus-within:ring-emerald-300 focus-within:ring-offset-2 focus-within:ring-offset-emerald-950 sm:mt-9">
                    <input name="q" type="search" placeholder="ابحث في المحتوى..." class="min-w-0 flex-1 border-0 bg-transparent px-4 text-slate-900 placeholder:text-slate-400 focus:ring-0">
                    <button class="shrink-0 rounded-xl bg-emerald-600 px-4 py-3 text-sm font-bold text-white transition hover:bg-emerald-700 sm:px-6">بحث</button>
                </form>
            </div>
            <div class="hidden items-center justify-center lg:flex">
                <div class="relative grid h-80 w-80 place-items-center rounded-full border border-emerald-300/20 bg-gradient-to-br from-emerald-800/60 to-teal-800/40 text-9xl shadow-2xl">
                    <div class="absolute inset-6 rounded-full border border-dashed border-emerald-300/20"></div>
                    ۞
                </div>
            </div>
        </div>

        <svg class="relative block h-12 w-full text-slate-50" viewBox="0 0 1200 48" preserveAspectRatio="none" fill="currentColor"><path d="M0 48 C300 0 900 0 1200 48 Z"></path></svg>
    </section>

    @if($biography)
        <section class="mx-auto -mt-2 max-w-7xl px-5 lg:px-8">
            <a href="{{ route('biography.show') }}" class="group flex flex-col items-center gap-6 rounded-3xl bg-gradient-to-l from-amber-50 to-white p-8 ring-1 ring-amber-100 transition hover:shadow-lg sm:flex-row sm:text-right">
                <span class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-amber-100 text-2xl">👤</span>
                <div class="flex-1">
                    <p class="text-sm font-bold text-amber-800">السيرة المختصرة</p>
                    <h2 class="mt-1 text-2xl font-extrabold">{{ $biography->title }}</h2>
                    <p class="mt-2 max-w-3xl leading-7 text-slate-600">{{ $biography->excerpt }}</p>
                </div>
                <span class="shrink-0 text-sm font-bold text-amber-800 transition group-hover:translate-x-[-4px]">قراءة السيرة كاملة ←</span>
            </a>
        </section>
    @endif

    <section class="mx-auto max-w-7xl px-5 py-16 lg:px-8">
        <div class="flex items-end justify-between">
            <div>
                <p class="text-sm font-bold text-emerald-700">اختيارات المنصة</p>
                <h2 class="mt-2 text-3xl font-extrabold tracking-tight">المحتوى المميز</h2>
            </div>
        </div>
        <div class="mt-8 grid gap-6 md:grid-cols-3">
            @forelse($featured as $item)
                <a href="{{ \App\Support\ContentUrl::for($item) }}" class="block rounded-3xl border border-slate-200 bg-white p-7 shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
                    <span class="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">{{ $typeLabels[$item->type] ?? $item->type }}</span>
                    <h3 class="mt-4 text-xl font-bold">{{ $item->title }}</h3>
                    <p class="mt-3 line-clamp-3 text-sm leading-7 text-slate-600">{{ $item->excerpt }}</p>
                </a>
            @empty
                <p class="col-span-full rounded-2xl bg-slate-100 p-8 text-center text-slate-500">سيظهر المحتوى المميز هنا عند نشره من لوحة التحكم.</p>
            @endforelse
        </div>
    </section>

    <section class="bg-slate-100/70">
        <div class="mx-auto max-w-7xl px-5 py-16 lg:px-8">
            <h2 class="text-3xl font-extrabold tracking-tight">التصنيفات الرئيسية</h2>
            <div class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-4 lg:grid-cols-8">
                @forelse($categories as $category)
                    <a href="{{ route('search', ['q' => $category->name]) }}" class="rounded-2xl bg-white p-5 text-center text-sm font-bold shadow-sm transition hover:-translate-y-0.5 hover:text-emerald-700 hover:shadow-md">{{ $category->name }}</a>
                @empty
                    <p class="col-span-full text-slate-500">ستظهر التصنيفات هنا عند إضافتها.</p>
                @endforelse
            </div>
        </div>
    </section>

    @foreach(['books' => ['أحدث الكتب', 'books'], 'lectures' => ['أحدث المحاضرات', 'lectures'], 'programs' => ['أحدث البرامج', 'programs'], 'reflections' => ['أحدث التأملات', 'reflections'], 'wallPosts' => ['أحدث منشورات الحائط', 'wall'], 'quranCentrality' => ['أبرز محتوى مركزية القرآن', 'quran-centrality'], 'quraniyat' => ['أبرز قرآنيات', 'quraniyat']] as $key => [$sectionTitle, $id])
        <section id="{{ $id }}" class="{{ $loop->even ? 'bg-slate-100/70' : '' }}">
            <div class="mx-auto max-w-7xl px-5 py-16 lg:px-8">
                <h2 class="text-3xl font-extrabold tracking-tight">{{ $sectionTitle }}</h2>
                <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    @forelse($$key as $item)
                        @php
                            $itemUrl = in_array($id, ['quran-centrality', 'quraniyat'], true)
                                ? route($id.'.show', $item->slug)
                                : \App\Support\ContentUrl::for($item);
                        @endphp
                        <a href="{{ $itemUrl }}" class="block rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
                            <p class="text-xs font-bold text-emerald-700">{{ $item->published_at?->translatedFormat('j M Y') }}</p>
                            <h3 class="mt-3 text-lg font-bold">{{ $item->title }}</h3>
                            <p class="mt-3 line-clamp-2 text-sm leading-6 text-slate-600">{{ $item->excerpt ?: \Illuminate\Support\Str::limit((string) $item->body, 100) }}</p>
                        </a>
                    @empty
                        <p class="col-span-full rounded-2xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">لا يوجد محتوى منشور في هذا القسم حاليًا.</p>
                    @endforelse
                </div>
            </div>
        </section>
    @endforeach
</main>
@endsection
