@extends('layouts.error')

@php($title = 'وصول غير مصرّح - ' . config('app.name'))

@section('content')
    <p class="text-7xl font-extrabold text-emerald-300 sm:text-8xl">403</p>
    <h1 class="mt-4 text-2xl font-bold sm:text-3xl">لا تملك صلاحية الوصول</h1>
    <p class="mt-3 leading-7 text-emerald-100/80">هذه الصفحة غير متاحة لحسابك الحالي. إذا كنت تعتقد أن هذا خطأ، تواصل مع مسؤول النظام.</p>
    <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
        <a href="{{ route('home') }}" class="rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-emerald-900 shadow-sm transition hover:bg-emerald-50">العودة إلى الرئيسية</a>
    </div>
@endsection
