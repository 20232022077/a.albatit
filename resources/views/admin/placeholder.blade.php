@extends('layouts.admin')

@section('admin-content')
    <section class="mx-auto max-w-3xl py-8 text-center sm:py-16">
        <div class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-emerald-100 text-2xl text-emerald-700">◌</div>
        <h1 class="mt-6 text-2xl font-bold">{{ $page['title'] }}</h1>
        <p class="mx-auto mt-3 max-w-xl leading-7 text-slate-500">{{ $page['description'] }}</p>
        <a href="{{ route('dashboard') }}" class="mt-8 inline-flex rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-white">العودة للرئيسية</a>
    </section>
@endsection
