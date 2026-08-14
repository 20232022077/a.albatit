@extends('layouts.error')

@php($title = 'انتهت صلاحية الجلسة - ' . config('app.name'))

@section('content')
    <p class="text-7xl font-extrabold text-emerald-300 sm:text-8xl">419</p>
    <h1 class="mt-4 text-2xl font-bold sm:text-3xl">انتهت صلاحية الصفحة</h1>
    <p class="mt-3 leading-7 text-emerald-100/80">استغرق الأمر وقتًا طويلاً منذ فتح هذه الصفحة. يرجى إعادة تحميلها والمحاولة مرة أخرى.</p>
    <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
        <a href="{{ url()->previous() }}" class="rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-emerald-900 shadow-sm transition hover:bg-emerald-50">إعادة المحاولة</a>
        <a href="{{ route('home') }}" class="rounded-xl bg-white/10 px-5 py-2.5 text-sm font-semibold text-white ring-1 ring-white/20 transition hover:bg-white/20">الصفحة الرئيسية</a>
    </div>
@endsection
