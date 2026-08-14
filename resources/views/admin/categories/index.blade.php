@extends('layouts.admin')

@php
    $sortLink = fn (string $column) => request()->fullUrlWithQuery(['sort' => $column, 'dir' => $sort === $column && $dir === 'asc' ? 'desc' : 'asc']);
@endphp

@section('admin-content')
<main class="mx-auto max-w-6xl px-6 py-10">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">التصنيفات</h1>
            <p class="mt-1 text-sm text-slate-500">إدارة التصنيفات المستخدمة عبر أقسام المنصة.</p>
        </div>
        @can('permission', 'content.create')
            <a href="{{ route('admin.categories.create') }}" class="rounded-lg bg-emerald-700 px-4 py-2 font-medium text-white">تصنيف جديد</a>
        @endcan
    </div>

    @if(session('status'))<p class="mt-4 rounded-lg bg-emerald-50 p-3 text-emerald-800">{{ session('status') }}</p>@endif

    <form method="GET" class="mt-6 flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-4">
        <div class="min-w-[200px] flex-1"><label for="field-q" class="block text-xs font-medium text-slate-500">بحث</label><input type="text" id="field-q" name="q" value="{{ request('q') }}" placeholder="ابحث بالاسم أو الوصف" class="mt-1 w-full rounded border-slate-300"></div>
        <div><label for="field-status" class="block text-xs font-medium text-slate-500">الحالة</label>
            <select id="field-status" name="status" class="mt-1 rounded border-slate-300">
                <option value="">الكل</option>
                <option value="active" @selected(request('status') === 'active')>مفعّل</option>
                <option value="inactive" @selected(request('status') === 'inactive')>معطّل</option>
            </select>
        </div>
        <label class="flex items-center gap-2 pb-2 text-sm"><input type="checkbox" name="trashed" value="1" @checked(request()->boolean('trashed'))> عرض المحذوفات</label>
        <div class="flex gap-2 pb-0.5"><button class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white">تصفية</button><a href="{{ route('admin.categories.index') }}" class="px-3 py-2 text-sm text-slate-500">إعادة تعيين</a></div>
    </form>

    <div class="mt-6 overflow-x-auto rounded-xl border border-slate-200">
        <table class="min-w-full text-right text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="p-4"></th>
                    <th class="p-4"><a href="{{ $sortLink('name') }}">الاسم</a></th>
                    <th class="p-4">التصنيف الأب</th>
                    <th class="p-4">الحالة</th>
                    <th class="p-4"><a href="{{ $sortLink('sort_order') }}">الترتيب</a></th>
                    <th class="p-4"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $category)
                    <tr class="border-t border-slate-200">
                        <td class="p-4">
                            @if($category->image)
                                <img loading="lazy" src="{{ $category->image->displayUrl() }}" alt="" class="h-12 w-12 rounded-lg object-cover">
                            @else
                                <span class="grid h-12 w-12 place-items-center rounded-lg bg-slate-100 text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="p-4 font-medium">{{ $category->name }}</td>
                        <td class="p-4 text-slate-500">{{ $category->parent?->name ?? '—' }}</td>
                        <td class="p-4">
                            @if($category->trashed())
                                <span class="rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700">محذوف</span>
                            @elseif($category->is_active)
                                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">مفعّل</span>
                            @else
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">معطّل</span>
                            @endif
                        </td>
                        <td class="p-4">{{ $category->sort_order }}</td>
                        <td class="p-4 whitespace-nowrap">
                            @if($category->trashed())
                                @can('permission', 'content.update')
                                    <form class="inline" method="POST" action="{{ route('admin.categories.restore', $category->id) }}">@csrf<button class="text-emerald-700">استعادة</button></form>
                                @endcan
                            @else
                                @can('permission', 'content.update')
                                    <a class="text-emerald-700" href="{{ route('admin.categories.edit', $category) }}">تعديل</a>
                                    @if($category->is_active)
                                        <form class="inline" method="POST" action="{{ route('admin.categories.deactivate', $category) }}">@csrf<button class="mr-3 text-amber-700">تعطيل</button></form>
                                    @else
                                        <form class="inline" method="POST" action="{{ route('admin.categories.activate', $category) }}">@csrf<button class="mr-3 text-emerald-700">تفعيل</button></form>
                                    @endif
                                @endcan
                                @can('permission', 'content.delete')
                                    <form class="inline" method="POST" action="{{ route('admin.categories.destroy', $category) }}">@csrf @method('DELETE')<button class="mr-3 text-red-700" onclick="return confirm('حذف هذا التصنيف؟')">حذف</button></form>
                                @endcan
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-6 text-center text-slate-500">لا توجد تصنيفات مطابقة.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5">{{ $items->links() }}</div>
</main>
@endsection
