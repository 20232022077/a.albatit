@extends('layouts.admin')

@section('admin-content')
    <main class="mx-auto max-w-7xl px-6 py-10">
        <div class="flex items-center justify-between gap-4"><h1 class="text-2xl font-bold">المستخدمون</h1>@can('create', App\Models\User::class)<a href="{{ route('admin.users.create') }}" class="rounded-lg bg-emerald-700 px-4 py-2 font-medium text-white">مستخدم جديد</a>@endcan</div>
        @if(session('status'))<p class="mt-4 rounded-lg bg-emerald-50 p-3 text-emerald-800">{{ session('status') }}</p>@endif
        <div class="mt-6 overflow-x-auto rounded-xl border border-slate-200"><table class="min-w-full text-right text-sm"><thead class="bg-slate-50"><tr><th class="p-4">الاسم</th><th class="p-4">البريد</th><th class="p-4">الأدوار</th><th class="p-4">الحالة</th><th class="p-4"></th></tr></thead><tbody>@forelse($users as $user)<tr class="border-t border-slate-200"><td class="p-4">{{ $user->name }}</td><td class="p-4" dir="ltr">{{ $user->email }}</td><td class="p-4">{{ $user->roles->pluck('display_name')->join('، ') ?: '—' }}</td><td class="p-4">{{ $user->is_active ? 'مفعّل' : 'معطّل' }}</td><td class="p-4 whitespace-nowrap">@can('update', $user)<a class="text-emerald-700" href="{{ route('admin.users.edit', $user) }}">تعديل</a>@endcan @can('delete', $user)<form class="inline" method="POST" action="{{ route('admin.users.destroy', $user) }}" data-confirm="حذف المستخدم؟">@csrf @method('DELETE')<button class="mr-3 text-red-700">حذف</button></form>@endcan</td></tr>@empty<tr><td colspan="5" class="p-6 text-center text-slate-500">لا يوجد مستخدمون.</td></tr>@endforelse</tbody></table></div>
        <div class="mt-5">{{ $users->links() }}</div>
    </main>
@endsection
