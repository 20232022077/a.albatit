@extends('layouts.app')

@section('content')
    <div class="min-h-screen bg-slate-50" data-admin-shell>
        <div class="fixed inset-0 z-30 hidden bg-slate-950/50 lg:hidden" data-sidebar-overlay></div>
        <aside class="fixed inset-y-0 right-0 z-40 flex w-72 translate-x-full flex-col bg-slate-900 text-slate-300 transition-transform duration-200 lg:translate-x-0" data-sidebar>
            <div class="flex h-20 items-center gap-3 border-b border-slate-800 px-6"><div class="grid h-10 w-10 place-items-center rounded-xl bg-emerald-500 font-bold text-white">إ</div><div><p class="font-bold text-white">المنصة الإسلامية</p><p class="text-xs text-slate-400">لوحة الإدارة</p></div></div>
            <nav class="flex-1 space-y-1 overflow-y-auto p-4">
                <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'bg-emerald-600 text-white' : 'hover:bg-slate-800' }} flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium">الرئيسية</a>
                @can('permission', 'content.view')<p class="px-4 pt-5 text-xs font-semibold text-slate-500">المحتوى</p><a href="{{ route('admin.section', 'content') }}" class="{{ request()->segment(2) === 'content' ? 'bg-slate-800 text-white' : 'hover:bg-slate-800' }} block rounded-lg px-4 py-2.5 text-sm">إدارة المحتوى</a><a href="{{ route('admin.quran-centrality.index') }}" class="{{ request()->routeIs('admin.quran-centrality.*') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800' }} block rounded-lg px-4 py-2.5 text-sm">مركزية القرآن</a><a href="{{ route('admin.section', 'media') }}" class="hover:bg-slate-800 block rounded-lg px-4 py-2.5 text-sm">الوسائط</a><a href="{{ route('admin.section', 'categories') }}" class="hover:bg-slate-800 block rounded-lg px-4 py-2.5 text-sm">التصنيفات</a><a href="{{ route('admin.section', 'tags') }}" class="hover:bg-slate-800 block rounded-lg px-4 py-2.5 text-sm">الوسوم</a>@endcan
                @can('viewAny', App\Models\User::class)<p class="px-4 pt-5 text-xs font-semibold text-slate-500">الإدارة</p><a href="{{ route('admin.users.index') }}" class="hover:bg-slate-800 block rounded-lg px-4 py-2.5 text-sm">المستخدمون</a>@endcan
                @can('viewAny', App\Models\Role::class)<a href="{{ route('admin.roles.index') }}" class="hover:bg-slate-800 block rounded-lg px-4 py-2.5 text-sm">الأدوار والصلاحيات</a>@endcan
                @can('permission', 'settings.manage')<a href="{{ route('admin.section', 'settings') }}" class="hover:bg-slate-800 block rounded-lg px-4 py-2.5 text-sm">إعدادات الموقع</a>@endcan
                @can('permission', 'activity_logs.view')<a href="{{ route('admin.section', 'activity-logs') }}" class="hover:bg-slate-800 block rounded-lg px-4 py-2.5 text-sm">سجل العمليات</a>@endcan
                @can('permission', 'backups.manage')<a href="{{ route('admin.section', 'backups') }}" class="hover:bg-slate-800 block rounded-lg px-4 py-2.5 text-sm">النسخ الاحتياطية</a>@endcan
            </nav>
            <div class="border-t border-slate-800 p-4"><form method="POST" action="{{ route('logout') }}">@csrf<button class="w-full rounded-lg bg-slate-800 px-4 py-2.5 text-sm hover:bg-slate-700">تسجيل الخروج</button></form></div>
        </aside>
        <div class="lg:pr-72"><header class="sticky top-0 z-20 flex h-20 items-center justify-between border-b border-slate-200 bg-white/90 px-5 backdrop-blur lg:px-8"><button @click="sidebarOpen = true" class="rounded-lg p-2 hover:bg-slate-100 lg:hidden" aria-label="فتح القائمة">☰</button><div class="hidden lg:block"><p class="text-sm text-slate-500">مرحبًا بك</p><p class="font-semibold">{{ auth()->user()->name }}</p></div><div class="mr-auto flex items-center gap-3"><span class="hidden text-sm text-slate-500 sm:block">{{ now()->translatedFormat('l، j F Y') }}</span><div class="grid h-10 w-10 place-items-center rounded-full bg-emerald-100 font-semibold text-emerald-800">{{ mb_substr(auth()->user()->name, 0, 1) }}</div></div></header>
            <main class="p-5 lg:p-8">@yield('admin-content')</main>
        </div>
    </div>
@endsection
