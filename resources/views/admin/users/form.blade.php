@extends('layouts.admin')

@section('admin-content')
    <main class="mx-auto max-w-3xl px-6 py-10"><h1 class="text-2xl font-bold">{{ $user->exists ? 'تعديل مستخدم' : 'مستخدم جديد' }}</h1><form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" class="mt-6 space-y-5 rounded-xl border border-slate-200 p-6">@csrf @if($user->exists) @method('PUT') @endif
        <div><label>الاسم</label><input name="name" value="{{ old('name', $user->name) }}" required class="mt-1 w-full rounded border-slate-300">@error('name')<p class="text-red-700">{{ $message }}</p>@enderror</div>
        <div><label>اسم المستخدم</label><input name="username" value="{{ old('username', $user->username) }}" class="mt-1 w-full rounded border-slate-300"></div>
        <div><label>البريد الإلكتروني</label><input type="email" name="email" dir="ltr" value="{{ old('email', $user->email) }}" required class="mt-1 w-full rounded border-slate-300">@error('email')<p class="text-red-700">{{ $message }}</p>@enderror</div>
        <div><label>كلمة المرور {{ $user->exists ? '(اتركها فارغة للإبقاء عليها)' : '' }}</label><input type="password" name="password" {{ $user->exists ? '' : 'required' }} class="mt-1 w-full rounded border-slate-300"></div><div><label>تأكيد كلمة المرور</label><input type="password" name="password_confirmation" {{ $user->exists ? '' : 'required' }} class="mt-1 w-full rounded border-slate-300"></div>
        @if($user->exists)<label class="flex gap-2"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active))> المستخدم مفعّل</label>@endif
        <fieldset><legend class="font-medium">الأدوار</legend><div class="mt-2 grid gap-2 sm:grid-cols-2">@foreach($roles as $role)<label class="flex gap-2"><input type="checkbox" name="role_ids[]" value="{{ $role->id }}" @checked(in_array($role->id, old('role_ids', $user->roles->pluck('id')->all())))>{{ $role->display_name }}</label>@endforeach</div></fieldset>
        <div class="flex gap-3"><button class="rounded bg-emerald-700 px-4 py-2 text-white">حفظ</button><a href="{{ route('admin.users.index') }}" class="px-4 py-2">إلغاء</a></div></form></main>
@endsection
