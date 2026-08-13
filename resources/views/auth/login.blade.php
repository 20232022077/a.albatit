@extends('layouts.app')

@section('content')
    <main class="flex min-h-screen items-center justify-center bg-slate-50 px-4 py-12">
        <section class="w-full max-w-md rounded-2xl bg-white p-8 shadow-sm ring-1 ring-slate-200">
            <h1 class="text-2xl font-bold">تسجيل الدخول</h1>
            <p class="mt-2 text-sm text-slate-600">الدخول مخصص لإدارة المنصة.</p>

            <form method="POST" action="{{ route('login.store') }}" class="mt-8 space-y-5">
                @csrf
                <div>
                    <label for="email" class="mb-2 block text-sm font-medium">البريد الإلكتروني</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="w-full rounded-lg border-slate-300 text-right shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                    @error('email') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="password" class="mb-2 block text-sm font-medium">كلمة المرور</label>
                    <input id="password" name="password" type="password" required autocomplete="current-password" class="w-full rounded-lg border-slate-300 text-right shadow-sm focus:border-emerald-600 focus:ring-emerald-600">
                    @error('password') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-600">
                    تذكرني
                </label>
                <button type="submit" class="w-full rounded-lg bg-emerald-700 px-4 py-3 font-semibold text-white transition hover:bg-emerald-800">دخول</button>
            </form>
        </section>
    </main>
@endsection
