@extends('layouts.admin')

@php
    $socialLabels = ['facebook' => 'فيسبوك', 'twitter' => 'X (تويتر)', 'youtube' => 'يوتيوب', 'instagram' => 'إنستغرام', 'telegram' => 'تيليجرام', 'whatsapp' => 'واتساب'];
    $socialLinks = $settings->socialLinks();
@endphp

@section('admin-content')
<main class="mx-auto max-w-3xl px-6 py-10">
    <h1 class="text-2xl font-bold">إعدادات الموقع</h1>
    <p class="mt-2 text-sm text-slate-500">الإعدادات المركزية للموقع: الهوية، التواصل، السيو، والواجهة. هذه الصفحة لا تخزّن أي كلمات مرور أو مفاتيح API — تلك تُدار عبر ملف .env على الخادم.</p>
    @if(session('status'))<p class="mt-4 rounded-lg bg-emerald-50 p-3 text-emerald-800">{{ session('status') }}</p>@endif

    <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="mt-6 space-y-8">
        @csrf
        @method('PUT')

        <section class="space-y-5 rounded-xl border border-slate-200 bg-white p-6">
            <h2 class="text-lg font-bold">عام</h2>

            <div>
                <label for="field-site_name" class="text-sm font-medium">اسم الموقع</label>
                <input id="field-site_name" name="site_name" value="{{ old('site_name', $settings->siteName()) }}" class="mt-1 w-full rounded border-slate-300">
                @error('site_name')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="field-site_description" class="text-sm font-medium">وصف الموقع</label>
                <textarea id="field-site_description" name="site_description" rows="2" maxlength="500" class="mt-1 w-full rounded border-slate-300">{{ old('site_description', $settings->siteDescription()) }}</textarea>
                <p class="mt-1 text-xs text-slate-500">يظهر في تذييل الموقع، ويُستخدم كوصف افتراضي للصفحات التي لا تملك وصفًا خاصًا (Meta Description).</p>
                @error('site_description')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
        </section>

        <section class="space-y-5 rounded-xl border border-slate-200 bg-white p-6">
            <h2 class="text-lg font-bold">التواصل</h2>

            <div>
                <label for="field-site_email" class="text-sm font-medium">البريد الرسمي</label>
                <input id="field-site_email" type="email" dir="ltr" name="site_email" value="{{ old('site_email', $settings->siteEmail()) }}" class="mt-1 w-full rounded border-slate-300">
                @error('site_email')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="field-site_phone" class="text-sm font-medium">رقم الهاتف</label>
                <input id="field-site_phone" dir="ltr" name="site_phone" value="{{ old('site_phone', $settings->sitePhone()) }}" class="mt-1 w-full rounded border-slate-300">
                @error('site_phone')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
        </section>

        <section class="space-y-5 rounded-xl border border-slate-200 bg-white p-6">
            <h2 class="text-lg font-bold">الهوية البصرية</h2>

            <div>
                <label class="text-sm font-medium">الشعار (Logo)</label>
                @if($settings->logoUrl())<img loading="lazy" src="{{ $settings->logoUrl() }}" alt="" class="mt-2 h-14 w-auto rounded bg-slate-50 object-contain p-1 ring-1 ring-slate-200">@endif
                <input type="file" name="logo" accept="image/*" class="mt-2 w-full text-sm">
                @error('logo')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="text-sm font-medium">Favicon</label>
                @if($settings->faviconUrl())<img loading="lazy" src="{{ $settings->faviconUrl() }}" alt="" class="mt-2 h-8 w-8 rounded bg-slate-50 object-contain p-1 ring-1 ring-slate-200">@endif
                <input type="file" name="favicon" accept="image/png" class="mt-2 w-full text-sm">
                <p class="mt-1 text-xs text-slate-500">صورة PNG مربعة (يُفضّل 512×512).</p>
                @error('favicon')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
        </section>

        <section class="space-y-4 rounded-xl border border-slate-200 bg-white p-6">
            <h2 class="text-lg font-bold">وسائل التواصل الاجتماعي</h2>
            @foreach($socialLabels as $key => $label)
                <div>
                    <label for="field-social-{{ $key }}" class="text-sm font-medium">{{ $label }}</label>
                    <input id="field-social-{{ $key }}" dir="ltr" name="social[{{ $key }}]" value="{{ old("social.$key", $socialLinks[$key] ?? null) }}" placeholder="https://" class="mt-1 w-full rounded border-slate-300">
                    @error("social.$key")<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
            @endforeach
        </section>

        <section class="space-y-5 rounded-xl border border-slate-200 bg-white p-6">
            <h2 class="text-lg font-bold">السيو (SEO)</h2>

            <div>
                <label class="text-sm font-medium">صورة المشاركة الافتراضية (Open Graph)</label>
                @if($settings->defaultOgImageUrl())<img loading="lazy" src="{{ $settings->defaultOgImageUrl() }}" alt="" class="mt-2 h-28 w-auto rounded-lg object-cover">@endif
                <input type="file" name="default_og_image" accept="image/*" class="mt-2 w-full text-sm">
                <p class="mt-1 text-xs text-slate-500">تظهر عند مشاركة روابط الصفحات التي لا تملك صورة غلاف خاصة بها (المقاس المفضّل 1200×630).</p>
                @error('default_og_image')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="field-twitter_site" class="text-sm font-medium">حساب X (تويتر) الرسمي</label>
                <input id="field-twitter_site" dir="ltr" name="twitter_site" value="{{ old('twitter_site', $settings->twitterSite()) }}" placeholder="@username" class="mt-1 w-full rounded border-slate-300">
                @error('twitter_site')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="field-google_site_verification" class="text-sm font-medium">رمز تحقق Google Search Console</label>
                <input id="field-google_site_verification" dir="ltr" name="google_site_verification" value="{{ old('google_site_verification', $settings->googleSiteVerification()) }}" class="mt-1 w-full rounded border-slate-300 font-mono text-sm">
                @error('google_site_verification')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
        </section>

        <section class="space-y-5 rounded-xl border border-slate-200 bg-white p-6">
            <h2 class="text-lg font-bold">الواجهة</h2>

            <div>
                <label for="field-items_per_page" class="text-sm font-medium">عدد العناصر في الصفحة الواحدة (الكتب، المحاضرات، البرامج...)</label>
                <input id="field-items_per_page" type="number" min="4" max="48" name="items_per_page" value="{{ old('items_per_page', $settings->itemsPerPage()) }}" class="mt-1 w-32 rounded border-slate-300">
                @error('items_per_page')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>
        </section>

        <div class="flex justify-end">
            <button class="rounded-lg bg-emerald-700 px-6 py-2.5 text-sm font-semibold text-white">حفظ الإعدادات</button>
        </div>
    </form>
</main>
@endsection
