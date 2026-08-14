@extends('layouts.admin')

@php
    $sectionLabels = [
        'books' => 'الكتب', 'lectures' => 'المحاضرات', 'programs' => 'البرامج', 'program_episodes' => 'حلقات البرامج',
        'quran_centrality' => 'مركزية القرآن', 'quraniyat' => 'قرآنيات', 'reflections' => 'التأملات', 'wall_posts' => 'الحائط',
        'categories' => 'التصنيفات', 'tags' => 'الوسوم', 'media' => 'الوسائط', 'users' => 'المستخدمون', 'roles' => 'الأدوار والصلاحيات',
        'biography' => 'السيرة الذاتية', 'settings' => 'إعدادات الموقع', 'backups' => 'النسخ الاحتياطية', 'auth' => 'تسجيل الدخول',
    ];
    $actionLabels = [
        'created' => 'إنشاء', 'updated' => 'تعديل', 'deleted' => 'حذف', 'restored' => 'استعادة',
        'published' => 'نشر', 'unpublished' => 'إلغاء نشر', 'activated' => 'تفعيل', 'deactivated' => 'تعطيل',
        'uploaded' => 'رفع', 'force_deleted' => 'حذف نهائي', 'succeeded' => 'دخول ناجح', 'failed' => 'محاولة فاشلة',
        'throttled' => 'حظر مؤقت', 'logout' => 'خروج', 'login' => 'دخول', 'downloaded' => 'تحميل',
        'restore_failed' => 'فشل الاستعادة',
    ];
    $describe = function (string $event) use ($sectionLabels, $actionLabels) {
        $parts = explode('.', $event);
        $section = $sectionLabels[$parts[0]] ?? $parts[0];
        $action = $actionLabels[end($parts)] ?? end($parts);
        return $section.' — '.$action;
    };
@endphp

@section('admin-content')
<main class="mx-auto max-w-6xl px-6 py-10">
    <div>
        <h1 class="text-2xl font-bold">سجل العمليات</h1>
        <p class="mt-1 text-sm text-slate-500">سجل كامل بكل العمليات الإدارية الحساسة: من قام بها، متى، ومن أين.</p>
    </div>

    <form method="GET" class="mt-6 flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-4">
        <div class="min-w-[180px]">
            <label for="field-user_id" class="block text-xs font-medium text-slate-500">المستخدم</label>
            <select id="field-user_id" name="user_id" class="mt-1 w-full rounded border-slate-300">
                <option value="">الكل</option>
                @foreach($users as $user)<option value="{{ $user->id }}" @selected(($filters['user_id'] ?? null) == $user->id)>{{ $user->name }}</option>@endforeach
            </select>
        </div>
        <div class="min-w-[160px]">
            <label for="field-section" class="block text-xs font-medium text-slate-500">القسم</label>
            <select id="field-section" name="section" class="mt-1 w-full rounded border-slate-300">
                <option value="">الكل</option>
                @foreach($sections as $section)<option value="{{ $section }}" @selected(($filters['section'] ?? null) === $section)>{{ $sectionLabels[$section] ?? $section }}</option>@endforeach
            </select>
        </div>
        <div><label for="field-from" class="block text-xs font-medium text-slate-500">من تاريخ</label><input type="date" id="field-from" name="from" value="{{ $filters['from'] ?? '' }}" class="mt-1 rounded border-slate-300"></div>
        <div><label for="field-to" class="block text-xs font-medium text-slate-500">إلى تاريخ</label><input type="date" id="field-to" name="to" value="{{ $filters['to'] ?? '' }}" class="mt-1 rounded border-slate-300"></div>
        <div class="min-w-[160px] flex-1"><label for="field-q" class="block text-xs font-medium text-slate-500">بحث في العملية</label><input type="text" id="field-q" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="مثال: deleted" class="mt-1 w-full rounded border-slate-300"></div>
        <div class="flex gap-2 pb-0.5"><button class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white">تصفية</button><a href="{{ route('admin.activity-logs.index') }}" class="px-3 py-2 text-sm text-slate-500">إعادة تعيين</a></div>
    </form>

    <div class="mt-6 overflow-x-auto rounded-xl border border-slate-200">
        <table class="min-w-full text-right text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="p-4">المستخدم</th>
                    <th class="p-4">العملية</th>
                    <th class="p-4">القسم</th>
                    <th class="p-4">Record ID</th>
                    <th class="p-4">التفاصيل</th>
                    <th class="p-4">عنوان IP</th>
                    <th class="p-4">التاريخ والوقت</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr class="border-t border-slate-200 align-top">
                        <td class="p-4 font-medium">{{ $log->user?->name ?? 'زائر / غير مسجل' }}</td>
                        <td class="p-4">{{ $describe($log->event) }}<div class="text-xs text-slate-400" dir="ltr">{{ $log->event }}</div></td>
                        <td class="p-4 text-slate-500">{{ $sectionLabels[$log->section()] ?? $log->section() }}</td>
                        <td class="p-4 text-slate-500">{{ $log->subject_id ?? '—' }}</td>
                        <td class="p-4 text-slate-500">
                            @if(filled($log->properties))
                                <ul class="space-y-0.5">
                                    @foreach($log->properties as $key => $value)
                                        <li><span class="text-slate-400">{{ $key }}:</span> {{ is_array($value) ? implode('، ', $value) : $value }}</li>
                                    @endforeach
                                </ul>
                            @else
                                —
                            @endif
                        </td>
                        <td class="p-4 text-slate-500" dir="ltr">{{ $log->ip_address ?? '—' }}</td>
                        <td class="p-4 whitespace-nowrap text-slate-500">{{ $log->created_at?->translatedFormat('j M Y') }}<div class="text-xs">{{ $log->created_at?->format('H:i:s') }}</div></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="p-6 text-center text-slate-500">لا توجد عمليات مسجّلة مطابقة.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5">{{ $logs->links() }}</div>
</main>
@endsection
