@extends('layouts.admin')

@php
    $icons = ['document' => '📄', 'check' => '✅', 'users' => '👥', 'image' => '🖼️'];
@endphp

@section('admin-content')
<main class="mx-auto max-w-7xl px-6 py-10">
    <h1 class="text-2xl font-bold">الرئيسية</h1>
    <p class="mt-1 text-sm text-slate-500">نظرة سريعة على حالة المنصة.</p>

    <div class="mt-7 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
        @foreach($stats as $stat)
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="flex items-center gap-3">
                    <span class="grid h-11 w-11 place-items-center rounded-lg bg-emerald-50 text-xl">{{ $icons[$stat['icon']] ?? '◌' }}</span>
                    <div>
                        <p class="text-2xl font-bold">{{ number_format($stat['value']) }}</p>
                        <p class="text-sm text-slate-500">{{ $stat['label'] }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-8 rounded-xl border border-slate-200 bg-white p-6">
        <h2 class="text-lg font-bold">آخر العمليات</h2>
        <div class="mt-4 divide-y divide-slate-100">
            @forelse($recentActivities as $activity)
                <div class="flex items-center justify-between py-3 text-sm">
                    <span>{{ $activity->user_name ?? 'النظام' }} — <span class="text-slate-500">{{ $activity->event }}</span></span>
                    <span class="text-xs text-slate-400">{{ \Illuminate\Support\Carbon::parse($activity->created_at)->diffForHumans() }}</span>
                </div>
            @empty
                <p class="py-6 text-center text-sm text-slate-500">لا توجد عمليات مسجّلة بعد.</p>
            @endforelse
        </div>
        @can('permission', 'activity_logs.view')
            <a href="{{ route('admin.activity-logs.index') }}" class="mt-4 inline-block text-sm text-emerald-700">عرض سجل العمليات كاملًا ←</a>
        @endcan
    </div>
</main>
@endsection
