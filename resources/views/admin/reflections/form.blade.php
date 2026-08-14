@extends('layouts.admin')

@php
    $seo = $item->meta['seo'] ?? [];
    $selectedCategoryIds = old('category_ids', $item->relationLoaded('categories') ? $item->categories->pluck('id')->all() : []);
@endphp

@section('admin-content')
<main class="mx-auto max-w-4xl px-6 py-10">
    <h1 class="text-2xl font-bold">{{ $item->exists ? 'تعديل تأمل' : 'تأمل جديد' }}</h1>

    <form method="POST" action="{{ $item->exists ? route('admin.reflections.update', $item) : route('admin.reflections.store') }}" enctype="multipart/form-data" class="mt-6 space-y-6 rounded-xl border border-slate-200 bg-white p-6">
        @csrf
        @if($item->exists) @method('PUT') @endif

        <div>
            <label for="field-title" class="text-sm font-medium">العنوان</label>
            <input id="field-title" name="title" value="{{ old('title', $item->title) }}" required class="mt-1 w-full rounded border-slate-300">
            @error('title')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="field-slug" class="text-sm font-medium">الرابط المختصر (Slug)</label>
            <input id="field-slug" name="slug" dir="ltr" value="{{ old('slug', $item->slug) }}" placeholder="يُولَّد تلقائيًا من العنوان إذا تُرك فارغًا" class="mt-1 w-full rounded border-slate-300">
            @error('slug')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="field-excerpt" class="text-sm font-medium">وصف مختصر</label>
            <textarea id="field-excerpt" name="excerpt" rows="2" class="mt-1 w-full rounded border-slate-300">{{ old('excerpt', $item->excerpt) }}</textarea>
            @error('excerpt')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="field-body" class="text-sm font-medium">نص التأمل</label>
            <textarea id="field-body" name="body" rows="8" class="mt-1 w-full rounded border-slate-300">{{ old('body', $item->body) }}</textarea>
            @error('body')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="text-sm font-medium">الصورة</label>
            @if($cover = $item->coverImage())<img loading="lazy" src="{{ $cover->displayUrl() }}" alt="" class="mt-2 h-24 w-24 rounded-lg object-cover">@endif
            <input type="file" name="cover_image" accept="image/*" class="mt-2 w-full text-sm">
            @error('cover_image')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>

        <fieldset class="rounded-lg border border-slate-200 p-4">
            <legend class="px-1 text-sm font-medium">مرجع قرآني (اختياري)</legend>
            <div class="grid gap-5 sm:grid-cols-3">
                <div>
                    <label for="field-surah_number" class="text-sm">رقم السورة</label>
                    <input type="number" id="field-surah_number" name="surah_number" min="1" max="114" value="{{ old('surah_number', $reflection->surah_number) }}" class="mt-1 w-full rounded border-slate-300">
                    @error('surah_number')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="field-ayah_from" class="text-sm">من آية</label>
                    <input type="number" id="field-ayah_from" name="ayah_from" min="1" value="{{ old('ayah_from', $reflection->ayah_from) }}" class="mt-1 w-full rounded border-slate-300">
                    @error('ayah_from')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="field-ayah_to" class="text-sm">إلى آية</label>
                    <input type="number" id="field-ayah_to" name="ayah_to" min="1" value="{{ old('ayah_to', $reflection->ayah_to) }}" class="mt-1 w-full rounded border-slate-300">
                    @error('ayah_to')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
            </div>
        </fieldset>

        <div class="grid gap-5 sm:grid-cols-3">
            <div>
                <label for="field-status" class="text-sm font-medium">الحالة</label>
                <select id="field-status" name="status" class="mt-1 w-full rounded border-slate-300">
                    <option value="draft" @selected(old('status', $item->status) === 'draft')>مسودة</option>
                    <option value="published" @selected(old('status', $item->status) === 'published')>منشور</option>
                    <option value="unpublished" @selected(old('status', $item->status) === 'unpublished')>غير منشور</option>
                </select>
                @error('status')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="field-sort_order" class="text-sm font-medium">ترتيب العرض</label>
                <input type="number" id="field-sort_order" name="sort_order" min="0" value="{{ old('sort_order', $item->sort_order ?? 0) }}" class="mt-1 w-full rounded border-slate-300">
                @error('sort_order')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            <div class="flex items-end pb-2">
                <label class="flex items-center gap-2"><input type="hidden" name="is_featured" value="0"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $item->is_featured))> تأمل مميز</label>
            </div>
        </div>

        <div>
            <label for="field-published_at" class="text-sm font-medium">تاريخ النشر</label>
            <input type="datetime-local" id="field-published_at" name="published_at" value="{{ old('published_at', $item->published_at?->format('Y-m-d\TH:i')) }}" class="mt-1 w-full rounded border-slate-300">
            @error('published_at')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>

        <fieldset>
            <legend class="text-sm font-medium">التصنيفات (عند الحاجة)</legend>
            <div class="mt-2 grid gap-2 sm:grid-cols-2">
                @forelse($categories as $category)
                    <label class="flex gap-2 rounded border border-slate-200 p-2"><input type="checkbox" name="category_ids[]" value="{{ $category->id }}" @checked(in_array($category->id, $selectedCategoryIds))> {{ $category->name }}</label>
                @empty
                    <p class="text-sm text-slate-400">لا توجد تصنيفات بعد.</p>
                @endforelse
            </div>
        </fieldset>

        <div>
            <label for="field-tags" class="text-sm font-medium">الوسوم (مفصولة بفواصل، عند الحاجة)</label>
            <input id="field-tags" name="tags" value="{{ old('tags', $item->relationLoaded('tags') ? $item->tags->pluck('name')->join(', ') : '') }}" class="mt-1 w-full rounded border-slate-300">
            @error('tags')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>

        <fieldset class="rounded-lg border border-slate-200 p-4">
            <legend class="px-1 text-sm font-medium">تحسين محركات البحث (SEO)</legend>
            <div class="space-y-4">
                <div><label for="field-seo_title" class="text-sm">عنوان SEO</label><input id="field-seo_title" name="seo_title" value="{{ old('seo_title', $seo['title'] ?? '') }}" class="mt-1 w-full rounded border-slate-300"></div>
                <div><label for="field-seo_description" class="text-sm">وصف SEO</label><textarea id="field-seo_description" name="seo_description" rows="2" class="mt-1 w-full rounded border-slate-300">{{ old('seo_description', $seo['description'] ?? '') }}</textarea></div>
                <div><label for="field-seo_keywords" class="text-sm">كلمات مفتاحية</label><input id="field-seo_keywords" name="seo_keywords" dir="ltr" value="{{ old('seo_keywords', $seo['keywords'] ?? '') }}" class="mt-1 w-full rounded border-slate-300"></div>
            </div>
        </fieldset>

        <div class="flex gap-3">
            <button class="rounded bg-emerald-700 px-5 py-2 text-white">حفظ</button>
            <a href="{{ route('admin.reflections.index') }}" class="px-4 py-2">إلغاء</a>
        </div>
    </form>
</main>
@endsection
