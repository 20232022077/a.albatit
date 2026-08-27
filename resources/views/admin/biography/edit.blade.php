@extends('layouts.admin')

@php
    $typeLabels = ['qualification' => 'مؤهل علمي', 'work' => 'خبرة مهنية', 'development' => 'نشاط علمي وتطويري', 'teaching' => 'نشاط تعليمي حالي', 'achievement' => 'إنجاز'];
@endphp

@section('admin-content')
<main class="mx-auto max-w-4xl px-6 py-10">
    <h1 class="text-2xl font-bold">السيرة الذاتية</h1>
    @if(session('status'))<p class="mt-4 rounded-lg bg-emerald-50 p-3 text-emerald-800">{{ session('status') }}</p>@endif

    <form method="POST" action="{{ route('admin.biography.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6 rounded-xl border border-slate-200 bg-white p-6">
        @csrf
        @method('PUT')

        <input type="hidden" name="is_visible" value="0">
        <label class="flex items-center gap-2"><input type="checkbox" name="is_visible" value="1" @checked($biography?->contentItem?->status === 'published')> إظهار القسم بالموقع</label>

        <div>
            <label for="field-name" class="text-sm font-medium">الاسم</label>
            <input id="field-name" name="name" required value="{{ old('name', $biography?->contentItem?->title) }}" class="mt-1 w-full rounded border-slate-300">
        </div>

        <div>
            <label for="field-excerpt" class="text-sm font-medium">النبذة</label>
            <textarea id="field-excerpt" name="excerpt" rows="2" class="mt-1 w-full rounded border-slate-300">{{ old('excerpt', $biography?->contentItem?->excerpt) }}</textarea>
        </div>

        <div>
            <h2 class="text-lg font-bold">معلومات أساسية</h2>
            <div class="mt-3 grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="field-city" class="text-sm font-medium">المدينة</label>
                    <input id="field-city" name="city" value="{{ old('city', $biography?->contentItem?->meta['city'] ?? null) }}" class="mt-1 w-full rounded border-slate-300">
                </div>
                <div>
                    <label for="field-birth_year_hijri" class="text-sm font-medium">سنة الميلاد (هجري)</label>
                    <input id="field-birth_year_hijri" name="birth_year_hijri" value="{{ old('birth_year_hijri', $biography?->contentItem?->meta['birth_year_hijri'] ?? null) }}" class="mt-1 w-full rounded border-slate-300">
                </div>
                <div>
                    <label for="field-contact_email" class="text-sm font-medium">البريد الإلكتروني</label>
                    <input id="field-contact_email" type="email" dir="ltr" name="contact_email" value="{{ old('contact_email', $biography?->contentItem?->meta['contact_email'] ?? null) }}" class="mt-1 w-full rounded border-slate-300">
                </div>
                <div>
                    <label for="field-contact_phone" class="text-sm font-medium">الجوال</label>
                    <input id="field-contact_phone" dir="ltr" name="contact_phone" value="{{ old('contact_phone', $biography?->contentItem?->meta['contact_phone'] ?? null) }}" class="mt-1 w-full rounded border-slate-300">
                </div>
            </div>
            <p class="mt-1 text-xs text-slate-500">تظهر هذه المعلومات في بطاقة "معلومات أساسية" بصفحة السيرة الذاتية — أي حقل تتركه فارغًا لا يظهر.</p>
        </div>

        <div>
            <label for="field-body" class="text-sm font-medium">النص التفصيلي</label>
            <textarea id="field-body" name="body" rows="6" class="mt-1 w-full rounded border-slate-300">{{ old('body', $biography?->contentItem?->body) }}</textarea>
        </div>

        <div>
            <label class="text-sm font-medium">الصورة الشخصية</label>
            @if($biography?->profileImage)<img loading="lazy" src="{{ $biography->profileImage->displayUrl() }}" alt="" class="mt-2 h-24 w-24 rounded-full object-cover">@endif
            <input type="file" name="profile_image" accept="image/*" class="mt-2 w-full text-sm">
        </div>

        <div>
            <label for="field-social_links" class="text-sm font-medium">روابط التواصل (JSON)</label>
            <textarea id="field-social_links" name="social_links" rows="3" dir="ltr" class="mt-1 w-full rounded border-slate-300 font-mono text-sm">{{ old('social_links', json_encode($biography?->contentItem?->meta['social_links'] ?? new stdClass, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) }}</textarea>
            <p class="mt-1 text-xs text-slate-500">مثال: {"twitter": "https://x.com/...", "youtube": "https://youtube.com/..."}</p>
            @error('social_links')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
        </div>

        <div>
            <div class="flex items-center justify-between pt-4">
                <h2 class="text-lg font-bold">العناصر المرتبة (مؤهلات، خبرات، أنشطة، إنجازات)</h2>
                <button type="button" id="add-section" class="rounded-lg bg-slate-800 px-3 py-1.5 text-sm text-white">+ إضافة عنصر</button>
            </div>
            <div id="sections-container" class="mt-3 space-y-3">
                @foreach($biography?->sections ?? [] as $i => $section)
                    <div class="section-row grid gap-3 rounded border border-slate-200 p-4 sm:grid-cols-2">
                        <input type="hidden" name="sections[{{ $i }}][id]" value="{{ $section->id }}">
                        <select name="sections[{{ $i }}][type]" class="rounded border-slate-300">
                            @foreach($typeLabels as $value => $label)<option value="{{ $value }}" @selected($section->type === $value)>{{ $label }}</option>@endforeach
                        </select>
                        <input name="sections[{{ $i }}][title]" value="{{ $section->title }}" placeholder="العنوان" class="rounded border-slate-300">
                        <textarea name="sections[{{ $i }}][body]" placeholder="التفاصيل" class="rounded border-slate-300 sm:col-span-2">{{ $section->body }}</textarea>
                        <input type="number" name="sections[{{ $i }}][sort_order]" value="{{ $section->sort_order }}" placeholder="الترتيب" class="rounded border-slate-300">
                        <div class="flex items-center justify-between">
                            <label class="flex items-center gap-2"><input type="hidden" name="sections[{{ $i }}][is_visible]" value="0"><input type="checkbox" name="sections[{{ $i }}][is_visible]" value="1" @checked($section->is_visible)> ظاهر</label>
                            <button type="button" class="remove-section text-sm text-red-700">حذف هذا العنصر</button>
                        </div>
                    </div>
                @endforeach
            </div>
            <p id="no-sections-hint" class="mt-3 text-sm text-slate-400 {{ ($biography?->sections->count() ?? 0) > 0 ? 'hidden' : '' }}">لا توجد عناصر بعد.</p>
        </div>

        <button class="rounded bg-emerald-700 px-5 py-2 text-white">حفظ</button>
    </form>
</main>

<template id="section-template">
    <div class="section-row grid gap-3 rounded border border-slate-200 p-4 sm:grid-cols-2">
        <input type="hidden" name="sections[__INDEX__][id]" value="">
        <select name="sections[__INDEX__][type]" class="rounded border-slate-300">
            @foreach($typeLabels as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
        </select>
        <input name="sections[__INDEX__][title]" placeholder="العنوان" class="rounded border-slate-300">
        <textarea name="sections[__INDEX__][body]" placeholder="التفاصيل" class="rounded border-slate-300 sm:col-span-2"></textarea>
        <input type="number" name="sections[__INDEX__][sort_order]" value="0" placeholder="الترتيب" class="rounded border-slate-300">
        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2"><input type="hidden" name="sections[__INDEX__][is_visible]" value="0"><input type="checkbox" name="sections[__INDEX__][is_visible]" value="1" checked> ظاهر</label>
            <button type="button" class="remove-section text-sm text-red-700">حذف هذا العنصر</button>
        </div>
    </div>
</template>
@endsection
