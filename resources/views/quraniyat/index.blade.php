@extends('layouts.public')

@php
    $typeLabels = ['article' => 'مقال', 'khatira' => 'خاطرة', 'fawaid' => 'فائدة', 'video' => 'فيديو يوتيوب', 'pdf' => 'ملف PDF'];
    $title = 'قرآنيات - ' . config('app.name');
    $metaDescription = 'مقالات وخواطر وفوائد وفيديوهات وملفات حول القرآن الكريم.';
@endphp

@section('public-content')
<main class="mx-auto max-w-6xl px-5 py-12">
    <h1 class="text-3xl font-bold">قرآنيات</h1>
    <p class="mt-3 max-w-2xl leading-7 text-slate-600">{{ $metaDescription }}</p>

    <form action="{{ route('quraniyat.index') }}" class="mt-7 flex flex-wrap gap-3">
        <input name="q" value="{{ $query }}" placeholder="ابحث في هذا القسم" class="min-w-[220px] flex-1 rounded-lg border-slate-300">
        <select name="type" class="rounded-lg border-slate-300" onchange="this.form.submit()">
            <option value="">كل الأنواع</option>
            @foreach($typeLabels as $key => $label)<option value="{{ $key }}" @selected($type === $key)>{{ $label }}</option>@endforeach
        </select>
        <button class="rounded-lg bg-emerald-700 px-5 py-2 text-white">بحث</button>
    </form>

    <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($items as $item)
            <a href="{{ route('quraniyat.show', $item->slug) }}" class="block overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:shadow-md">
                @if($cover = $item->coverImage())
                    <img loading="lazy" src="{{ $cover->displayUrl() }}" alt="{{ $item->title }}" class="h-40 w-full object-cover">
                @else
                    <div class="grid h-40 w-full place-items-center bg-emerald-50 text-4xl text-emerald-700">۞</div>
                @endif
                <div class="p-5">
                    <span class="text-xs font-semibold text-emerald-700">{{ $typeLabels[$item->type] ?? $item->type }}</span>
                    <h2 class="mt-2 text-lg font-bold">{{ $item->title }}</h2>
                    <p class="mt-2 line-clamp-2 text-sm leading-6 text-slate-600">{{ $item->excerpt }}</p>
                </div>
            </a>
        @empty
            <p class="col-span-full rounded-xl bg-slate-100 p-8 text-center text-slate-500">لا يوجد محتوى منشور حاليًا في هذا القسم.</p>
        @endforelse
    </div>
    <div class="mt-8">{{ $items->links() }}</div>
</main>
@endsection
