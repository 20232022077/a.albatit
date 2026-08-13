@extends('layouts.admin')

@section('admin-content')
<main class="mx-auto max-w-2xl px-6 py-10">
    <h1 class="text-2xl font-bold">{{ $tag->exists ? 'تعديل وسم' : 'وسم جديد' }}</h1>

    <form method="POST" action="{{ $tag->exists ? route('admin.tags.update', $tag) : route('admin.tags.store') }}" class="mt-6 space-y-6 rounded-xl border border-slate-200 bg-white p-6">
        @csrf
        @if($tag->exists) @method('PUT') @endif

        <div>
            <label class="text-sm font-medium">الاسم</label>
            <input name="name" value="{{ old('name', $tag->name) }}" required class="mt-1 w-full rounded border-slate-300">
            @error('name')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="text-sm font-medium">الرابط المختصر (Slug)</label>
            <input name="slug" dir="ltr" value="{{ old('slug', $tag->slug) }}" placeholder="يُولَّد تلقائيًا من الاسم إذا تُرك فارغًا" class="mt-1 w-full rounded border-slate-300">
            @error('slug')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>

        <label class="flex items-center gap-2"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $tag->is_active))> وسم مفعّل</label>

        <div class="flex gap-3">
            <button class="rounded bg-emerald-700 px-5 py-2 text-white">حفظ</button>
            <a href="{{ route('admin.tags.index') }}" class="px-4 py-2">إلغاء</a>
        </div>
    </form>
</main>
@endsection
