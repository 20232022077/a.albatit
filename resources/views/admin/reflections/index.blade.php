@extends('layouts.admin')

@php
    $sortLink = fn (string $column) => request()->fullUrlWithQuery(['sort' => $column, 'dir' => $sort === $column && $dir === 'asc' ? 'desc' : 'asc']);
@endphp

@section('admin-content')
<main class="mx-auto max-w-7xl px-6 py-10">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">التأملات</h1>
            <p class="mt-1 text-sm text-slate-500">إدارة التأملات القرآنية.</p>
        </div>
        @can('permission', 'content.create')
            <a href="{{ route('admin.reflections.create') }}" class="rounded-lg bg-emerald-700 px-4 py-2 font-medium text-white">تأمل جديد</a>
        @endcan
    </div>

    @if(session('status'))<p class="mt-4 rounded-lg bg-emerald-50 p-3 text-emerald-800">{{ session('status') }}</p>@endif

    <form method="GET" class="mt-6 flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-4">
        <div class="min-w-[200px] flex-1"><label for="field-q" class="block text-xs font-medium text-slate-500">بحث</label><input type="text" id="field-q" name="q" value="{{ request('q') }}" placeholder="ابحث بالعنوان أو النص" class="mt-1 w-full rounded border-slate-300"></div>
        <div><label for="field-status" class="block text-xs font-medium text-slate-500">الحالة</label>
            <select id="field-status" name="status" class="mt-1 rounded border-slate-300">
                <option value="">الكل</option>
                <option value="published" @selected(request('status') === 'published')>منشور</option>
                <option value="draft" @selected(request('status') === 'draft')>مسودة</option>
                <option value="unpublished" @selected(request('status') === 'unpublished')>غير منشور</option>
            </select>
        </div>
        <div><label for="field-category_id" class="block text-xs font-medium text-slate-500">التصنيف</label>
            <select id="field-category_id" name="category_id" class="mt-1 rounded border-slate-300">
                <option value="">الكل</option>
                @foreach($categories as $category)<option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>@endforeach
            </select>
        </div>
        <label class="flex items-center gap-2 pb-2 text-sm"><input type="checkbox" name="trashed" value="1" @checked(request()->boolean('trashed'))> عرض المحذوفات</label>
        <div class="flex gap-2 pb-0.5"><button class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white">تصفية</button><a href="{{ route('admin.reflections.index') }}" class="px-3 py-2 text-sm text-slate-500">إعادة تعيين</a></div>
    </form>

    <div class="mt-6 overflow-x-auto rounded-xl border border-slate-200">
        <table class="min-w-full text-right text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="p-4"></th>
                    <th class="p-4"><a href="{{ $sortLink('title') }}">العنوان</a></th>
                    <th class="p-4">التصنيفات</th>
                    <th class="p-4">الحالة</th>
                    <th class="p-4">مميز</th>
                    <th class="p-4"><a href="{{ $sortLink('sort_order') }}">الترتيب</a></th>
                    <th class="p-4"><a href="{{ $sortLink('published_at') }}">تاريخ النشر</a></th>
                    <th class="p-4"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                    <tr class="border-t border-slate-200">
                        <td class="p-4">
                            @if($cover = $item->coverImage())
                                <img loading="lazy" src="{{ $cover->displayUrl() }}" alt="" class="h-12 w-12 rounded-lg object-cover">
                            @else
                                <span class="grid h-12 w-12 place-items-center rounded-lg bg-slate-100 text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="p-4 font-medium">{{ $item->title }}</td>
                        <td class="p-4 text-slate-500">{{ $item->categories->pluck('name')->join('، ') ?: '—' }}</td>
                        <td class="p-4">
                            @if($item->trashed())
                                <span class="rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700">محذوف</span>
                            @elseif($item->status === 'published')
                                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">منشور</span>
                            @elseif($item->status === 'unpublished')
                                <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700">غير منشور</span>
                            @else
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">مسودة</span>
                            @endif
                        </td>
                        <td class="p-4">{{ $item->is_featured ? '★' : '—' }}</td>
                        <td class="p-4">{{ $item->sort_order }}</td>
                        <td class="p-4 text-slate-500">{{ $item->published_at?->translatedFormat('j M Y') ?? '—' }}</td>
                        <td class="p-4 whitespace-nowrap">
                            @if($item->trashed())
                                @can('permission', 'content.update')
                                    <form class="inline" method="POST" action="{{ route('admin.reflections.restore', $item->id) }}">@csrf<button class="text-emerald-700">استعادة</button></form>
                                @endcan
                            @else
                                @can('permission', 'content.update')
                                    <a class="text-emerald-700" href="{{ route('admin.reflections.edit', $item) }}">تعديل</a>
                                    @if($item->status === 'published')
                                        <form class="inline" method="POST" action="{{ route('admin.reflections.unpublish', $item) }}">@csrf<button class="mr-3 text-amber-700">إلغاء النشر</button></form>
                                    @else
                                        <form class="inline" method="POST" action="{{ route('admin.reflections.publish', $item) }}">@csrf<button class="mr-3 text-emerald-700">نشر</button></form>
                                    @endif
                                @endcan
                                @can('permission', 'content.delete')
                                    <form class="inline" method="POST" action="{{ route('admin.reflections.destroy', $item) }}" data-confirm="حذف هذا التأمل؟">@csrf @method('DELETE')<button class="mr-3 text-red-700">حذف</button></form>
                                @endcan
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="p-6 text-center text-slate-500">لا توجد تأملات مطابقة.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5">{{ $items->links() }}</div>
</main>
@endsection
