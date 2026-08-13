@extends('layouts.app')

@section('content')
<header class="sticky top-0 z-30 bg-emerald-950/95 backdrop-blur-lg">
    <div class="mx-auto flex h-20 max-w-7xl items-center justify-between gap-5 px-5 lg:px-8">
        <a href="{{ route('home') }}" class="flex items-center">
            <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}" class="h-12 w-auto sm:h-14">
        </a>
        <nav class="hidden items-center gap-1 text-sm font-medium text-emerald-100 md:flex">
            <a href="{{ route('home') }}" class="rounded-lg px-3 py-2 transition hover:bg-white/10 hover:text-white">الرئيسية</a>
            <a href="{{ route('biography.show') }}" class="rounded-lg px-3 py-2 transition hover:bg-white/10 hover:text-white">السيرة الذاتية</a>
            <a href="{{ route('quran-centrality.index') }}" class="rounded-lg px-3 py-2 transition hover:bg-white/10 hover:text-white">مركزية القرآن</a>
            <a href="{{ route('books.index') }}" class="rounded-lg px-3 py-2 transition hover:bg-white/10 hover:text-white">الكتب</a>
            <a href="{{ route('quraniyat.index') }}" class="rounded-lg px-3 py-2 transition hover:bg-white/10 hover:text-white">قرآنيات</a>
            <a href="{{ route('home') }}#programs" class="rounded-lg px-3 py-2 transition hover:bg-white/10 hover:text-white">البرامج</a>
            <a href="{{ route('lectures.index') }}" class="rounded-lg px-3 py-2 transition hover:bg-white/10 hover:text-white">المحاضرات</a>
            <a href="{{ route('reflections.index') }}" class="rounded-lg px-3 py-2 transition hover:bg-white/10 hover:text-white">تأملات</a>
            <a href="{{ route('wall.index') }}" class="rounded-lg px-3 py-2 transition hover:bg-white/10 hover:text-white">حائط</a>
        </nav>
        <a href="{{ route('login') }}" class="rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-emerald-900 shadow-sm transition hover:bg-emerald-50">دخول الإدارة</a>
    </div>
</header>

@yield('public-content')

<footer class="bg-slate-950 text-slate-300">
    <div class="mx-auto grid max-w-7xl gap-10 px-5 py-16 sm:grid-cols-2 lg:grid-cols-3 lg:px-8">
        <div>
            <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}" class="h-12 w-auto">
            <p class="mt-4 text-sm leading-7 text-slate-400">منصة عربية لتقديم المحتوى الإسلامي المعرفي.</p>
        </div>
        <div>
            <p class="font-bold text-white">روابط سريعة</p>
            <div class="mt-4 grid grid-cols-2 gap-2 text-sm">
                <a class="transition hover:text-white" href="{{ route('biography.show') }}">السيرة الذاتية</a>
                <a class="transition hover:text-white" href="{{ route('quran-centrality.index') }}">مركزية القرآن</a>
                <a class="transition hover:text-white" href="{{ route('books.index') }}">الكتب</a>
                <a class="transition hover:text-white" href="{{ route('quraniyat.index') }}">قرآنيات</a>
                <a class="transition hover:text-white" href="{{ route('lectures.index') }}">المحاضرات</a>
                <a class="transition hover:text-white" href="{{ route('reflections.index') }}">تأملات</a>
                <a class="transition hover:text-white" href="{{ route('wall.index') }}">حائط</a>
            </div>
        </div>
        <div>
            <p class="font-bold text-white">البحث</p>
            <form action="{{ route('search') }}" class="mt-4 flex gap-2">
                <input name="q" class="min-w-0 flex-1 rounded-xl border-0 bg-slate-800 px-4 py-2.5 text-sm text-white placeholder:text-slate-500" placeholder="كلمة البحث">
                <button class="rounded-xl bg-emerald-600 px-4 text-sm font-semibold transition hover:bg-emerald-700">بحث</button>
            </form>
        </div>
    </div>
    <div class="border-t border-slate-800 py-6 text-center text-xs text-slate-500">© {{ now()->year }} {{ config('app.name') }}</div>
</footer>
@endsection
