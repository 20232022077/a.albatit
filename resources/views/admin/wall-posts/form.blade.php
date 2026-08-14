@extends('layouts.admin')

@section('admin-content')
<main class="mx-auto max-w-2xl px-6 py-10">
    <h1 class="text-2xl font-bold">{{ $item->exists ? 'تعديل منشور' : 'منشور جديد' }} — الحائط</h1>

    <form method="POST" action="{{ $item->exists ? route('admin.wall-posts.update', $item) : route('admin.wall-posts.store') }}" enctype="multipart/form-data" class="mt-6 space-y-6 rounded-xl border border-slate-200 bg-white p-6">
        @csrf
        @if($item->exists) @method('PUT') @endif

        <div>
            <label class="text-sm font-medium">النص</label>
            <textarea name="text" rows="5" maxlength="2000" required class="mt-1 w-full rounded border-slate-300">{{ old('text', $item->body) }}</textarea>
            @error('text')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="text-sm font-medium">صورة (اختياري)</label>
            @if($cover = $item->coverImage())<img src="{{ $cover->url() }}" alt="" class="mt-2 h-24 w-24 rounded-lg object-cover">@endif
            <input type="file" name="cover_image" accept="image/*" class="mt-2 w-full text-sm">
            @error('cover_image')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="text-sm font-medium">رابط فيديو يوتيوب (اختياري)</label>
            <input name="video_url" dir="ltr" value="{{ old('video_url', $item->meta['video_url'] ?? '') }}" placeholder="https://www.youtube.com/watch?v=..." class="mt-1 w-full rounded border-slate-300">
            @error('video_url')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>

        <div class="grid gap-5 sm:grid-cols-3">
            <div>
                <label class="text-sm font-medium">الحالة</label>
                <select name="status" class="mt-1 w-full rounded border-slate-300">
                    <option value="draft" @selected(old('status', $item->status) === 'draft')>مسودة</option>
                    <option value="published" @selected(old('status', $item->status) === 'published')>منشور</option>
                    <option value="unpublished" @selected(old('status', $item->status) === 'unpublished')>غير منشور</option>
                </select>
                @error('status')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="text-sm font-medium">ترتيب العرض (عند الحاجة)</label>
                <input type="number" name="sort_order" min="0" value="{{ old('sort_order', $item->sort_order ?? 0) }}" class="mt-1 w-full rounded border-slate-300">
                @error('sort_order')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="text-sm font-medium">تاريخ النشر</label>
                <input type="datetime-local" name="published_at" value="{{ old('published_at', $item->published_at?->format('Y-m-d\TH:i')) }}" class="mt-1 w-full rounded border-slate-300">
                @error('published_at')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="flex flex-wrap gap-6">
            <label class="flex items-center gap-2"><input type="hidden" name="is_featured" value="0"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $item->is_featured))> مميز (عند الحاجة)</label>
            <label class="flex items-center gap-2"><input type="hidden" name="is_pinned" value="0"><input type="checkbox" name="is_pinned" value="1" @checked(old('is_pinned', $wallPost->is_pinned))> مثبّت أعلى الحائط</label>
        </div>

        <div class="flex gap-3">
            <button class="rounded bg-emerald-700 px-5 py-2 text-white">حفظ</button>
            <a href="{{ route('admin.wall-posts.index') }}" class="px-4 py-2">إلغاء</a>
        </div>
    </form>
</main>
@endsection
