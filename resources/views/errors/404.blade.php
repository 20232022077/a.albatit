@extends('layouts.error')

@php($title = 'الصفحة غير موجودة - ' . config('app.name'))

@section('content')
    <p class="text-7xl font-extrabold text-emerald-300 sm:text-8xl">404</p>
    <h1 class="mt-4 text-2xl font-bold sm:text-3xl">الصفحة غير موجودة</h1>
    <p class="mt-3 leading-7 text-emerald-100/80">قد يكون الرابط غير صحيح أو تم نقل هذا المحتوى إلى مكان آخر.</p>
    <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
        <a href="{{ route('home') }}" class="rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-emerald-900 shadow-sm transition hover:bg-emerald-50">العودة إلى الرئيسية</a>
        <a href="{{ route('search') }}" class="rounded-xl bg-white/10 px-5 py-2.5 text-sm font-semibold text-white ring-1 ring-white/20 transition hover:bg-white/20">البحث في الموقع</a>
    </div>
@endsection
