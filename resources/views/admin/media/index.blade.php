@extends('layouts.admin')

@section('admin-content')
<main class="mx-auto max-w-6xl px-6 py-10">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">مكتبة الوسائط</h1>
            <p class="mt-1 text-sm text-slate-500">رفع الصور وملفات PDF واستخدامها عبر أقسام المنصة. الصور تحصل على نسخ WebP/AVIF تلقائية، وملفات PDF تُخزَّن في مساحة خاصة غير قابلة للوصول المباشر وتُعرض عبر رابط آمن.</p>
        </div>
    </div>

    @if(session('status'))<p class="mt-4 rounded-lg bg-emerald-50 p-3 text-emerald-800">{{ session('status') }}</p>@endif
    @if(session('error'))<p class="mt-4 rounded-lg bg-red-50 p-3 text-red-700">{{ session('error') }}</p>@endif
    @if($errors->any())
        <div class="mt-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">
            <ul class="list-inside list-disc">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    @can('permission', 'content.create')
        <form method="POST" action="{{ route('admin.media.store') }}" enctype="multipart/form-data" class="mt-6 flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-4">
            @csrf
            <div class="min-w-[260px] flex-1">
                <label class="block text-xs font-medium text-slate-500">رفع صور أو ملفات PDF جديدة (يمكن اختيار أكثر من ملف)</label>
                <input type="file" name="files[]" multiple accept="image/jpeg,image/png,image/gif,image/webp,image/bmp,image/avif,application/pdf" required class="mt-1 w-full rounded border-slate-300 text-sm">
            </div>
            <button class="rounded-lg bg-emerald-700 px-5 py-2.5 text-sm font-medium text-white">رفع</button>
        </form>
    @endcan

    <form method="GET" class="mt-4 flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-4">
        <div class="min-w-[220px] flex-1"><label class="block text-xs font-medium text-slate-500">بحث</label><input type="text" name="q" value="{{ request('q') }}" placeholder="ابحث بالاسم أو النص البديل أو الوصف" class="mt-1 w-full rounded border-slate-300"></div>
        <div><label class="block text-xs font-medium text-slate-500">النوع</label>
            <select name="type" class="mt-1 rounded border-slate-300">
                <option value="">الكل</option>
                <option value="image" @selected(request('type') === 'image')>صور</option>
                <option value="pdf" @selected(request('type') === 'pdf')>ملفات PDF</option>
            </select>
        </div>
        <label class="flex items-center gap-2 pb-2 text-sm"><input type="checkbox" name="trashed" value="1" @checked(request()->boolean('trashed'))> عرض المحذوفات</label>
        <div class="flex gap-2 pb-0.5"><button class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white">تصفية</button><a href="{{ route('admin.media.index') }}" class="px-3 py-2 text-sm text-slate-500">إعادة تعيين</a></div>
    </form>

    <div class="mt-6 grid grid-cols-2 gap-5 sm:grid-cols-3 lg:grid-cols-4">
        @forelse($items as $item)
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                @if($item->isPdf())
                    <a href="{{ $item->pdfUrl() }}" target="_blank" rel="noopener" class="flex aspect-square flex-col items-center justify-center gap-2 bg-slate-100 text-red-700">
                        <span class="text-4xl">📄</span>
                        <span class="text-xs font-medium">عرض PDF</span>
                    </a>
                @else
                    <div class="aspect-square bg-slate-100">
                        <img src="{{ $item->webpUrl() ?? $item->url() }}" alt="{{ $item->alt_text }}" loading="lazy" class="h-full w-full object-cover">
                    </div>
                @endif
                <div class="space-y-1 p-3 text-xs text-slate-500">
                    <p class="truncate font-medium text-slate-800" title="{{ $item->original_name }}">{{ $item->original_name }}</p>
                    @if($item->isPdf())
                        <p>{{ $item->humanSize() }}</p>
                        <p class="text-emerald-700">مخزَّن في مساحة خاصة · يُعرض عبر رابط آمن</p>
                    @else
                        <p>{{ $item->dimensions() ?? '—' }} · {{ $item->humanSize() }}</p>
                    @endif
                    <p dir="ltr" class="truncate">{{ $item->mime_type }}</p>
                    @if(($item->metadata['variants'] ?? []) !== [])
                        <p class="text-emerald-700">نسخ محسّنة: {{ implode('، ', array_keys($item->metadata['variants'])) }}</p>
                    @endif
                    @if($item->content_items_count > 0)
                        <p class="text-emerald-700">مستخدمة في {{ $item->content_items_count }} محتوى</p>
                    @else
                        <p>غير مستخدمة حاليًا</p>
                    @endif
                </div>

                @if($item->trashed())
                    <div class="border-t border-slate-100 p-3">
                        <span class="mb-2 inline-block rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700">محذوفة</span>
                        @can('permission', 'content.update')
                            <form method="POST" action="{{ route('admin.media.restore', $item->id) }}">@csrf
                                <button class="w-full rounded-lg bg-emerald-50 py-1.5 text-xs font-medium text-emerald-700">استعادة</button>
                            </form>
                        @endcan
                        @can('permission', 'content.delete')
                            <form method="POST" action="{{ route('admin.media.force-destroy', $item->id) }}" class="mt-1.5" onsubmit="return confirm('حذف هذه الصورة نهائيًا؟ لا يمكن التراجع عن هذا الإجراء.')">@csrf @method('DELETE')
                                <button class="w-full rounded-lg bg-red-50 py-1.5 text-xs font-medium text-red-700">حذف نهائي</button>
                            </form>
                        @endcan
                    </div>
                @else
                    @can('permission', 'content.update')
                        <form method="POST" action="{{ route('admin.media.update', $item) }}" class="space-y-2 border-t border-slate-100 p-3">
                            @csrf @method('PUT')
                            <div>
                                <label class="block text-[11px] font-medium text-slate-500">النص البديل (Alt)</label>
                                <input type="text" name="alt_text" value="{{ $item->alt_text }}" class="mt-0.5 w-full rounded border-slate-300 text-xs">
                            </div>
                            <div>
                                <label class="block text-[11px] font-medium text-slate-500">تعليق (Caption)</label>
                                <input type="text" name="caption" value="{{ $item->caption }}" class="mt-0.5 w-full rounded border-slate-300 text-xs">
                            </div>
                            <button class="w-full rounded-lg bg-slate-800 py-1.5 text-xs font-medium text-white">حفظ</button>
                        </form>
                        @can('permission', 'content.delete')
                            <form method="POST" action="{{ route('admin.media.destroy', $item) }}" class="border-t border-slate-100 p-3" onsubmit="return confirm('نقل هذه الصورة إلى المحذوفات؟')">@csrf @method('DELETE')
                                <button class="w-full rounded-lg bg-red-50 py-1.5 text-xs font-medium text-red-700">حذف</button>
                            </form>
                        @endcan
                    @endcan
                @endif
            </div>
        @empty
            <p class="col-span-full rounded-xl bg-slate-100 p-8 text-center text-slate-500">لا توجد ملفات مطابقة.</p>
        @endforelse
    </div>
    <div class="mt-6">{{ $items->links() }}</div>
</main>
@endsection
