@extends('layouts.app')

@php($robots = 'noindex,nofollow')

@section('content')
    <div class="min-h-screen bg-slate-50" data-admin-shell>
        <div class="fixed inset-0 z-30 hidden bg-slate-950/50 lg:hidden" data-sidebar-overlay></div>
        <aside id="admin-sidebar-nav" class="fixed inset-y-0 right-0 z-40 flex w-72 translate-x-full flex-col bg-slate-900 text-slate-300 transition-transform duration-200 lg:translate-x-0" data-sidebar aria-label="القائمة الرئيسية للوحة التحكم">
            <div class="flex h-20 items-center justify-between gap-2 border-b border-slate-800 px-6">
                <div class="flex flex-col items-start justify-center gap-1"><img src="{{ $siteSettings->logoUrl() ?? asset('images/logo.png') }}" alt="{{ $siteSettings->siteName() }}" class="h-9 w-auto"><p class="text-xs text-slate-400">لوحة الإدارة</p></div>
                <button type="button" data-sidebar-close class="grid h-9 w-9 shrink-0 place-items-center rounded-lg text-slate-300 transition hover:bg-slate-800 lg:hidden">
                    <span class="sr-only">إغلاق القائمة</span>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <nav class="flex-1 space-y-1 overflow-y-auto p-4">
                <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'bg-emerald-600 text-white' : 'hover:bg-slate-800' }} flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-medium">الرئيسية</a>
                @can('permission', 'content.view')<p class="px-4 pt-5 text-xs font-semibold text-slate-500">المحتوى</p><a href="{{ route('admin.biography.edit') }}" class="{{ request()->routeIs('admin.biography.*') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800' }} block rounded-lg px-4 py-2.5 text-sm">السيرة الذاتية</a><a href="{{ route('admin.section', 'content') }}" class="{{ request()->segment(2) === 'content' ? 'bg-slate-800 text-white' : 'hover:bg-slate-800' }} block rounded-lg px-4 py-2.5 text-sm">إدارة المحتوى</a><a href="{{ route('admin.quran-centrality.index') }}" class="{{ request()->routeIs('admin.quran-centrality.*') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800' }} block rounded-lg px-4 py-2.5 text-sm">مركزية القرآن</a><a href="{{ route('admin.books.index') }}" class="{{ request()->routeIs('admin.books.*') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800' }} block rounded-lg px-4 py-2.5 text-sm">الكتب</a><a href="{{ route('admin.quraniyat.index') }}" class="{{ request()->routeIs('admin.quraniyat.*') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800' }} block rounded-lg px-4 py-2.5 text-sm">قرآنيات</a><a href="{{ route('admin.programs.index') }}" class="{{ request()->routeIs('admin.programs.*') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800' }} block rounded-lg px-4 py-2.5 text-sm">البرامج</a><a href="{{ route('admin.lectures.index') }}" class="{{ request()->routeIs('admin.lectures.*') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800' }} block rounded-lg px-4 py-2.5 text-sm">المحاضرات</a><a href="{{ route('admin.reflections.index') }}" class="{{ request()->routeIs('admin.reflections.*') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800' }} block rounded-lg px-4 py-2.5 text-sm">التأملات</a><a href="{{ route('admin.wall-posts.index') }}" class="{{ request()->routeIs('admin.wall-posts.*') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800' }} block rounded-lg px-4 py-2.5 text-sm">الحائط</a><a href="{{ route('admin.media.index') }}" class="{{ request()->routeIs('admin.media.*') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800' }} block rounded-lg px-4 py-2.5 text-sm">الوسائط</a><a href="{{ route('admin.categories.index') }}" class="{{ request()->routeIs('admin.categories.*') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800' }} block rounded-lg px-4 py-2.5 text-sm">التصنيفات</a><a href="{{ route('admin.tags.index') }}" class="{{ request()->routeIs('admin.tags.*') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800' }} block rounded-lg px-4 py-2.5 text-sm">الوسوم</a>@endcan
                @can('viewAny', App\Models\User::class)<p class="px-4 pt-5 text-xs font-semibold text-slate-500">الإدارة</p><a href="{{ route('admin.users.index') }}" class="hover:bg-slate-800 block rounded-lg px-4 py-2.5 text-sm">المستخدمون</a>@endcan
                @can('viewAny', App\Models\Role::class)<a href="{{ route('admin.roles.index') }}" class="hover:bg-slate-800 block rounded-lg px-4 py-2.5 text-sm">الأدوار والصلاحيات</a>@endcan
                @can('permission', 'settings.manage')<a href="{{ route('admin.settings.edit') }}" class="{{ request()->routeIs('admin.settings.*') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800' }} block rounded-lg px-4 py-2.5 text-sm">إعدادات الموقع</a>@endcan
                @can('permission', 'activity_logs.view')<a href="{{ route('admin.activity-logs.index') }}" class="{{ request()->routeIs('admin.activity-logs.*') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800' }} block rounded-lg px-4 py-2.5 text-sm">سجل العمليات</a>@endcan
                @can('permission', 'backups.manage')<a href="{{ route('admin.backups.index') }}" class="{{ request()->routeIs('admin.backups.*') ? 'bg-slate-800 text-white' : 'hover:bg-slate-800' }} block rounded-lg px-4 py-2.5 text-sm">النسخ الاحتياطية</a>@endcan
            </nav>
            <div class="border-t border-slate-800 p-4"><form method="POST" action="{{ route('logout') }}">@csrf<button class="w-full rounded-lg bg-slate-800 px-4 py-2.5 text-sm hover:bg-slate-700">تسجيل الخروج</button></form></div>
        </aside>
        <div class="lg:pr-72"><header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-slate-200 bg-white/90 px-4 backdrop-blur sm:h-20 sm:px-5 lg:px-8">
                <button type="button" data-sidebar-open aria-expanded="false" aria-controls="admin-sidebar-nav" class="grid h-10 w-10 shrink-0 place-items-center rounded-lg text-slate-700 transition hover:bg-slate-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600 lg:hidden">
                    <span class="sr-only">فتح القائمة الرئيسية</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <div class="hidden lg:block"><p class="text-sm text-slate-500">مرحبًا بك</p><p class="font-semibold">{{ auth()->user()->name }}</p></div>
                <div class="flex items-center gap-2 sm:gap-3"><span class="hidden text-sm text-slate-500 sm:block">{{ now()->translatedFormat('l، j F Y') }}</span><div class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-emerald-100 text-sm font-semibold text-emerald-800 sm:h-10 sm:w-10">{{ mb_substr(auth()->user()->name, 0, 1) }}</div></div>
            </header>
            <main id="main-content" tabindex="-1" class="overflow-x-hidden p-4 sm:p-5 lg:p-8">@yield('admin-content')</main>
        </div>
    </div>
@endsection
