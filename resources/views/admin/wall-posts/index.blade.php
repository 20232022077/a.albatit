@extends('layouts.admin')

@section('admin-content')
<main class="mx-auto max-w-5xl px-6 py-10">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">الحائط</h1>
            <p class="mt-1 text-sm text-slate-500">منشورات قصيرة تظهر بصفحة الحائط العامة.</p>
        </div>
        @can('permission', 'content.create')
            <a href="{{ route('admin.wall-posts.create') }}" class="rounded-lg bg-emerald-700 px-4 py-2 font-medium text-white">منشور جديد</a>
        @endcan
    </div>

    @if(session('status'))<p class="mt-4 rounded-lg bg-emerald-50 p-3 text-emerald-800">{{ session('status') }}</p>@endif

    <form method="GET" class="mt-6 flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-4">
        <div class="min-w-[200px] flex-1"><label class="block text-xs font-medium text-slate-500">بحث</label><input type="text" name="q" value="{{ request('q') }}" placeholder="ابحث في نص المنشورات" class="mt-1 w-full rounded border-slate-300"></div>
        <div><label class="block text-xs font-medium text-slate-500">الحالة</label>
            <select name="status" class="mt-1 rounded border-slate-300">
                <option value="">الكل</option>
                <option value="published" @selected(request('status') === 'published')>منشور</option>
                <option value="draft" @selected(request('status') === 'draft')>مسودة</option>
                <option value="unpublished" @selected(request('status') === 'unpublished')>غير منشور</option>
            </select>
        </div>
        <label class="flex items-center gap-2 pb-2 text-sm"><input type="checkbox" name="trashed" value="1" @checked(request()->boolean('trashed'))> عرض المحذوفات</label>
        <div class="flex gap-2 pb-0.5"><button class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white">تصفية</button><a href="{{ route('admin.wall-posts.index') }}" class="px-3 py-2 text-sm text-slate-500">إعادة تعيين</a></div>
    </form>

    <div class="mt-6 space-y-3">
        @forelse($items as $item)
            <div class="flex items-start gap-4 rounded-xl border border-slate-200 bg-white p-4">
                @if($cover = $item->coverImage())
                    <img src="{{ $cover->url() }}" alt="" class="h-16 w-16 shrink-0 rounded-lg object-cover">
                @endif
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        @if($item->wallPost?->is_pinned)<span class="rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700">مثبّت</span>@endif
                        @if($item->trashed())
                            <span class="rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700">محذوف</span>
                        @elseif($item->status === 'published')
                            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">منشور</span>
                        @elseif($item->status === 'unpublished')
                            <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700">غير منشور</span>
                        @else
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">مسودة</span>
                        @endif
                        @if($item->is_featured)<span class="text-amber-500">★</span>@endif
                        <span class="text-xs text-slate-400">{{ $item->published_at?->translatedFormat('j M Y') ?? '—' }}</span>
                    </div>
                    <p class="mt-2 text-sm leading-6 text-slate-700">{{ \Illuminate\Support\Str::limit($item->body, 200) }}</p>
                </div>
                <div class="flex shrink-0 flex-col items-end gap-1 whitespace-nowrap text-sm">
                    @if($item->trashed())
                        @can('permission', 'content.update')
                            <form method="POST" action="{{ route('admin.wall-posts.restore', $item->id) }}">@csrf<button class="text-emerald-700">استعادة</button></form>
                        @endcan
                    @else
                        @can('permission', 'content.update')
                            <a class="text-emerald-700" href="{{ route('admin.wall-posts.edit', $item) }}">تعديل</a>
                            @if($item->status === 'published')
                                <form method="POST" action="{{ route('admin.wall-posts.unpublish', $item) }}">@csrf<button class="text-amber-700">إلغاء النشر</button></form>
                            @else
                                <form method="POST" action="{{ route('admin.wall-posts.publish', $item) }}">@csrf<button class="text-emerald-700">نشر</button></form>
                            @endif
                        @endcan
                        @can('permission', 'content.delete')
                            <form method="POST" action="{{ route('admin.wall-posts.destroy', $item) }}">@csrf @method('DELETE')<button class="text-red-700" onclick="return confirm('حذف هذا المنشور؟')">حذف</button></form>
                        @endcan
                    @endif
                </div>
            </div>
        @empty
            <p class="rounded-xl bg-slate-100 p-6 text-center text-slate-500">لا توجد منشورات مطابقة.</p>
        @endforelse
    </div>
    <div class="mt-5">{{ $items->links() }}</div>
</main>
@endsection
