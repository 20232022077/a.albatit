@extends('layouts.admin')

@section('admin-content')
<main class="mx-auto max-w-5xl px-6 py-10">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">النسخ الاحتياطية</h1>
            <p class="mt-1 text-sm text-slate-500">نسخة كاملة (قاعدة البيانات + الملفات المهمة) في أرشيف واحد. يُحتفظ تلقائيًا بآخر {{ $retention }} نسخ فقط.</p>
        </div>
        <form method="POST" action="{{ route('admin.backups.store') }}" data-confirm="إنشاء نسخة احتياطية جديدة الآن؟ قد تستغرق العملية بعض الوقت.">
            @csrf
            <button class="rounded-lg bg-emerald-700 px-4 py-2 font-medium text-white">إنشاء نسخة احتياطية</button>
        </form>
    </div>

    @if(session('status'))<p class="mt-4 rounded-lg bg-emerald-50 p-3 text-emerald-800">{{ session('status') }}</p>@endif
    @if(session('error'))<p class="mt-4 rounded-lg bg-red-50 p-3 text-red-800">{{ session('error') }}</p>@endif

    <div class="mt-6 overflow-x-auto rounded-xl border border-slate-200">
        <table class="min-w-full text-right text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="p-4">الملف</th>
                    <th class="p-4">الحجم</th>
                    <th class="p-4">الحالة</th>
                    <th class="p-4">بواسطة</th>
                    <th class="p-4">التاريخ</th>
                    <th class="p-4"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($backups as $backup)
                    <tr class="border-t border-slate-200">
                        <td class="p-4 font-medium" dir="ltr">{{ $backup->filename }}</td>
                        <td class="p-4 text-slate-500">{{ $backup->status === 'completed' ? $backup->humanSize() : '—' }}</td>
                        <td class="p-4">
                            @if($backup->status === 'completed')
                                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">مكتملة</span>
                            @else
                                <span class="rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700" title="{{ $backup->notes }}">فشلت</span>
                            @endif
                        </td>
                        <td class="p-4 text-slate-500">{{ $backup->creator?->name ?? '—' }}</td>
                        <td class="p-4 text-slate-500">{{ $backup->created_at->translatedFormat('j M Y') }}<div class="text-xs">{{ $backup->created_at->format('H:i:s') }}</div></td>
                        <td class="p-4 whitespace-nowrap">
                            @if($backup->status === 'completed')
                                <a class="text-emerald-700" href="{{ route('admin.backups.download', $backup) }}">تحميل</a>
                                <form class="inline" method="POST" action="{{ route('admin.backups.restore', $backup) }}" data-confirm="استعادة هذه النسخة ستستبدل قاعدة البيانات والملفات الحالية بالكامل بمحتوى النسخة الاحتياطية، ولا يمكن التراجع عن هذا الإجراء. هل أنت متأكد تمامًا؟">
                                    @csrf
                                    <button class="mr-3 text-amber-700">استعادة</button>
                                </form>
                            @endif
                            <form class="inline" method="POST" action="{{ route('admin.backups.destroy', $backup) }}" data-confirm="حذف هذه النسخة الاحتياطية نهائيًا؟">
                                @csrf @method('DELETE')
                                <button class="mr-3 text-red-700">حذف</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-6 text-center text-slate-500">لا توجد نسخ احتياطية بعد.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5">{{ $backups->links() }}</div>
</main>
@endsection
