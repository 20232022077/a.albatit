@extends('layouts.admin')

@section('admin-content')
<main class="mx-auto max-w-3xl px-6 py-10">
    <a href="{{ route('admin.programs.episodes.index', $program) }}" class="text-sm font-semibold text-emerald-700">← حلقات {{ $program->title }}</a>
    <h1 class="mt-3 text-2xl font-bold">{{ $item->exists ? 'تعديل حلقة' : 'حلقة جديدة' }}</h1>

    <form method="POST" action="{{ $item->exists ? route('admin.programs.episodes.update', [$program, $item]) : route('admin.programs.episodes.store', $program) }}" enctype="multipart/form-data" class="mt-6 space-y-6 rounded-xl border border-slate-200 bg-white p-6">
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
            <label for="field-excerpt" class="text-sm font-medium">الوصف</label>
            <textarea id="field-excerpt" name="excerpt" rows="3" class="mt-1 w-full rounded border-slate-300">{{ old('excerpt', $item->excerpt) }}</textarea>
            @error('excerpt')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="field-video_url" class="text-sm font-medium">رابط فيديو يوتيوب</label>
            <input id="field-video_url" name="video_url" dir="ltr" value="{{ old('video_url', $item->meta['video_url'] ?? '') }}" placeholder="https://www.youtube.com/watch?v=..." required class="mt-1 w-full rounded border-slate-300">
            @error('video_url')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="text-sm font-medium">الصورة (عند الحاجة)</label>
            @if($item->exists && $cover = $item->coverImage())<img loading="lazy" src="{{ $cover->displayUrl() }}" alt="" class="mt-2 h-24 w-24 rounded-lg object-cover">@endif
            <input type="file" name="cover_image" accept="image/*" class="mt-2 w-full text-sm">
            @error('cover_image')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>

        <div class="grid gap-5 sm:grid-cols-3">
            <div>
                <label for="field-status" class="text-sm font-medium">الحالة</label>
                <select id="field-status" name="status" class="mt-1 w-full rounded border-slate-300">
                    <option value="draft" @selected(old('status', $item->status ?? 'draft') === 'draft')>مسودة</option>
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
            <div>
                <label for="field-published_at" class="text-sm font-medium">تاريخ النشر</label>
                <input type="datetime-local" id="field-published_at" name="published_at" value="{{ old('published_at', $item->published_at?->format('Y-m-d\TH:i')) }}" class="mt-1 w-full rounded border-slate-300">
                @error('published_at')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="flex gap-3">
            <button class="rounded bg-emerald-700 px-5 py-2 text-white">حفظ</button>
            <a href="{{ route('admin.programs.episodes.index', $program) }}" class="px-4 py-2">إلغاء</a>
        </div>
    </form>
</main>
@endsection
