@extends('layouts.admin')

@php
    $seo = $category->meta['seo'] ?? [];
@endphp

@section('admin-content')
<main class="mx-auto max-w-3xl px-6 py-10">
    <h1 class="text-2xl font-bold">{{ $category->exists ? 'تعديل تصنيف' : 'تصنيف جديد' }}</h1>

    <form method="POST" action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}" enctype="multipart/form-data" class="mt-6 space-y-6 rounded-xl border border-slate-200 bg-white p-6">
        @csrf
        @if($category->exists) @method('PUT') @endif

        <div>
            <label for="field-name" class="text-sm font-medium">الاسم</label>
            <input id="field-name" name="name" value="{{ old('name', $category->name) }}" required class="mt-1 w-full rounded border-slate-300">
            @error('name')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="field-slug" class="text-sm font-medium">الرابط المختصر (Slug)</label>
            <input id="field-slug" name="slug" dir="ltr" value="{{ old('slug', $category->slug) }}" placeholder="يُولَّد تلقائيًا من الاسم إذا تُرك فارغًا" class="mt-1 w-full rounded border-slate-300">
            @error('slug')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="field-description" class="text-sm font-medium">الوصف</label>
            <textarea id="field-description" name="description" rows="3" class="mt-1 w-full rounded border-slate-300">{{ old('description', $category->description) }}</textarea>
            @error('description')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="field-parent_id" class="text-sm font-medium">التصنيف الأب (اختياري)</label>
            <select id="field-parent_id" name="parent_id" class="mt-1 w-full rounded border-slate-300">
                <option value="">بدون</option>
                @foreach($parents as $parent)<option value="{{ $parent->id }}" @selected((int) old('parent_id', $category->parent_id) === $parent->id)>{{ $parent->name }}</option>@endforeach
            </select>
            @error('parent_id')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="text-sm font-medium">الصورة (عند الحاجة)</label>
            @if($category->image)<img loading="lazy" src="{{ $category->image->displayUrl() }}" alt="" class="mt-2 h-24 w-24 rounded-lg object-cover">@endif
            <input type="file" name="image" accept="image/*" class="mt-2 w-full text-sm">
            @error('image')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="field-sort_order" class="text-sm font-medium">ترتيب العرض</label>
                <input type="number" id="field-sort_order" name="sort_order" min="0" value="{{ old('sort_order', $category->sort_order ?? 0) }}" class="mt-1 w-full rounded border-slate-300">
                @error('sort_order')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            <div class="flex items-end pb-2">
                <label class="flex items-center gap-2"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active))> تصنيف مفعّل</label>
            </div>
        </div>

        <fieldset class="rounded-lg border border-slate-200 p-4">
            <legend class="px-1 text-sm font-medium">تحسين محركات البحث (SEO) — عند الحاجة</legend>
            <div class="space-y-4">
                <div><label for="field-seo_title" class="text-sm">عنوان SEO</label><input id="field-seo_title" name="seo_title" value="{{ old('seo_title', $seo['title'] ?? '') }}" class="mt-1 w-full rounded border-slate-300"></div>
                <div><label for="field-seo_description" class="text-sm">وصف SEO</label><textarea id="field-seo_description" name="seo_description" rows="2" class="mt-1 w-full rounded border-slate-300">{{ old('seo_description', $seo['description'] ?? '') }}</textarea></div>
                <div><label for="field-seo_keywords" class="text-sm">كلمات مفتاحية</label><input id="field-seo_keywords" name="seo_keywords" dir="ltr" value="{{ old('seo_keywords', $seo['keywords'] ?? '') }}" class="mt-1 w-full rounded border-slate-300"></div>
            </div>
        </fieldset>

        <div class="flex gap-3">
            <button class="rounded bg-emerald-700 px-5 py-2 text-white">حفظ</button>
            <a href="{{ route('admin.categories.index') }}" class="px-4 py-2">إلغاء</a>
        </div>
    </form>
</main>
@endsection
