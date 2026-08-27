@extends('layouts.admin')

@section('admin-content')
    <main class="mx-auto max-w-7xl px-6 py-10">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold">لوحة التحكم</h1>
                <p class="mt-1 text-slate-600">مرحبًا {{ auth()->user()->name }}.</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50">تسجيل الخروج</button>
            </form>
        </div>
        <div class="mt-8 flex flex-wrap gap-3">
            @can('viewAny', App\Models\User::class)
                <a href="{{ route('admin.users.index') }}" class="rounded-lg bg-emerald-700 px-4 py-2 font-medium text-white">إدارة المستخدمين</a>
            @endcan
            @can('viewAny', App\Models\Role::class)
                <a href="{{ route('admin.roles.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 font-medium">الأدوار والصلاحيات</a>
            @endcan
        </div>
        <section class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach($stats as $stat)
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm text-slate-500">{{ $stat['label'] }}</p>
                    <p class="mt-3 text-3xl font-bold">{{ number_format($stat['value']) }}</p>
                </article>
            @endforeach
        </section>
        <section class="mt-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between"><h2 class="font-bold">آخر العمليات</h2>@can('permission', 'activity_logs.view')<a href="{{ route('admin.activity-logs.index') }}" class="text-sm text-emerald-700">عرض السجل</a>@endcan</div>
            <div class="mt-4 divide-y divide-slate-100">@forelse($recentActivities as $activity)<div class="flex items-center justify-between gap-3 py-3"><span class="text-sm font-medium">{{ $activity->event }}</span><span class="text-xs text-slate-400">{{ \Carbon\Carbon::parse($activity->created_at)->diffForHumans() }}</span></div>@empty<p class="py-6 text-center text-sm text-slate-500">لا توجد عمليات مسجلة بعد.</p>@endforelse</div>
        </section>
    </main>
@endsection
