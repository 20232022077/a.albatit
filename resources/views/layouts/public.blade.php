@extends('layouts.app')

@section('content')
<header class="sticky top-0 z-30 bg-emerald-950/95 backdrop-blur-lg">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-2 px-4 sm:h-20 sm:gap-5 sm:px-5 lg:px-8">
        <a href="{{ route('home') }}" class="flex min-w-0 items-center">
            <img src="{{ $siteSettings->logoUrl() ?? $siteSettings->versionedAsset('images/logo.png') }}" alt="{{ $siteSettings->siteName() }}" class="h-7 w-auto max-w-[110px] object-contain sm:h-12 sm:max-w-none lg:h-14">
        </a>
        <nav class="hidden items-center gap-1 text-sm font-medium text-emerald-100 md:flex" aria-label="القائمة الرئيسية">
            <a href="{{ route('home') }}" class="rounded-lg px-3 py-2 transition hover:bg-white/10 hover:text-white">الرئيسية</a>
            @unless($siteSettings->isSectionHidden('biography'))
                <a href="{{ route('biography.show') }}" class="rounded-lg px-3 py-2 transition hover:bg-white/10 hover:text-white">السيرة الذاتية</a>
            @endunless
            @unless($siteSettings->isSectionHidden('quran-centrality'))
                <a href="{{ route('quran-centrality.index') }}" class="rounded-lg px-3 py-2 transition hover:bg-white/10 hover:text-white">مركزية القرآن</a>
            @endunless
            @unless($siteSettings->isSectionHidden('quraniyat'))
                <a href="{{ route('quraniyat.index') }}" class="rounded-lg px-3 py-2 transition hover:bg-white/10 hover:text-white">قرآنيات</a>
            @endunless
            @unless($siteSettings->isSectionHidden('wall'))
                <a href="{{ route('wall.index') }}" class="rounded-lg px-3 py-2 transition hover:bg-white/10 hover:text-white">حائط</a>
            @endunless
            @unless($siteSettings->isSectionHidden('reflections'))
                <a href="{{ route('reflections.index') }}" class="rounded-lg px-3 py-2 transition hover:bg-white/10 hover:text-white">تأملات</a>
            @endunless
            @unless($siteSettings->isSectionHidden('books'))
                <a href="{{ route('books.index') }}" class="rounded-lg px-3 py-2 transition hover:bg-white/10 hover:text-white">الكتب</a>
            @endunless
            @unless($siteSettings->isSectionHidden('programs'))
                <a href="{{ route('programs.index') }}" class="rounded-lg px-3 py-2 transition hover:bg-white/10 hover:text-white">البرامج</a>
            @endunless
            @unless($siteSettings->isSectionHidden('lectures'))
                <a href="{{ route('lectures.index') }}" class="rounded-lg px-3 py-2 transition hover:bg-white/10 hover:text-white">المحاضرات</a>
            @endunless
        </nav>
        <div class="flex shrink-0 items-center gap-2 sm:gap-3">
            <a
                href="{{ route('search') }}"
                class="grid h-9 w-9 shrink-0 place-items-center rounded-lg text-white transition hover:bg-white/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
            >
                <span class="sr-only">بحث</span>
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m20 20-3.5-3.5"/></svg>
            </a>
            <button
                type="button"
                data-mobile-menu-toggle
                aria-expanded="false"
                aria-controls="mobile-nav-menu"
                class="grid h-9 w-9 shrink-0 place-items-center rounded-lg text-white transition hover:bg-white/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white md:hidden"
            >
                <span class="sr-only">فتح القائمة الرئيسية</span>
                <svg data-menu-icon-open class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                <svg data-menu-icon-close class="hidden h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"></path></svg>
            </button>
        </div>
    </div>

    <nav id="mobile-nav-menu" data-mobile-menu class="hidden border-t border-white/10 md:hidden" aria-label="القائمة الرئيسية للجوال">
        <div class="space-y-1 px-4 py-4 text-sm font-medium text-emerald-100">
            <a href="{{ route('home') }}" class="block rounded-lg px-3 py-2.5 transition hover:bg-white/10 hover:text-white">الرئيسية</a>
            @unless($siteSettings->isSectionHidden('biography'))
                <a href="{{ route('biography.show') }}" class="block rounded-lg px-3 py-2.5 transition hover:bg-white/10 hover:text-white">السيرة الذاتية</a>
            @endunless
            @unless($siteSettings->isSectionHidden('quran-centrality'))
                <a href="{{ route('quran-centrality.index') }}" class="block rounded-lg px-3 py-2.5 transition hover:bg-white/10 hover:text-white">مركزية القرآن</a>
            @endunless
            @unless($siteSettings->isSectionHidden('quraniyat'))
                <a href="{{ route('quraniyat.index') }}" class="block rounded-lg px-3 py-2.5 transition hover:bg-white/10 hover:text-white">قرآنيات</a>
            @endunless
            @unless($siteSettings->isSectionHidden('wall'))
                <a href="{{ route('wall.index') }}" class="block rounded-lg px-3 py-2.5 transition hover:bg-white/10 hover:text-white">حائط</a>
            @endunless
            @unless($siteSettings->isSectionHidden('reflections'))
                <a href="{{ route('reflections.index') }}" class="block rounded-lg px-3 py-2.5 transition hover:bg-white/10 hover:text-white">تأملات</a>
            @endunless
            @unless($siteSettings->isSectionHidden('books'))
                <a href="{{ route('books.index') }}" class="block rounded-lg px-3 py-2.5 transition hover:bg-white/10 hover:text-white">الكتب</a>
            @endunless
            @unless($siteSettings->isSectionHidden('programs'))
                <a href="{{ route('programs.index') }}" class="block rounded-lg px-3 py-2.5 transition hover:bg-white/10 hover:text-white">البرامج</a>
            @endunless
            @unless($siteSettings->isSectionHidden('lectures'))
                <a href="{{ route('lectures.index') }}" class="block rounded-lg px-3 py-2.5 transition hover:bg-white/10 hover:text-white">المحاضرات</a>
            @endunless
        </div>
    </nav>
</header>

<div id="main-content" tabindex="-1" data-reveal-scope>
@yield('public-content')
</div>

<footer class="bg-slate-950 text-slate-300">
    <div class="mx-auto grid max-w-7xl gap-10 px-5 py-16 sm:grid-cols-2 lg:grid-cols-3 lg:px-8">
        <div>
            <img src="{{ $siteSettings->logoUrl() ?? $siteSettings->versionedAsset('images/logo.png') }}" alt="{{ $siteSettings->siteName() }}" class="h-12 w-auto">
            <p class="mt-4 text-sm leading-7 text-slate-400">{{ $siteSettings->siteDescription() ?? 'منصة معرفية لتثوير القرآن وترسيخ مركزيته.' }}</p>

            @if($siteSettings->siteEmail() || $siteSettings->sitePhone())
                <div class="mt-4 space-y-1 text-sm text-slate-400">
                    @if($siteSettings->siteEmail())<a href="mailto:{{ $siteSettings->siteEmail() }}" dir="ltr" class="block text-right transition hover:text-white">{{ $siteSettings->siteEmail() }}</a>@endif
                    @if($siteSettings->sitePhone())<a href="tel:{{ $siteSettings->sitePhone() }}" dir="ltr" class="block text-right transition hover:text-white">{{ $siteSettings->sitePhone() }}</a>@endif
                </div>
            @endif

            @php($socialLabels = ['facebook' => 'فيسبوك', 'twitter' => 'X', 'youtube' => 'يوتيوب', 'instagram' => 'إنستغرام', 'telegram' => 'تيليجرام', 'whatsapp' => 'واتساب'])
            @if(filled($siteSettings->socialLinks()))
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach($siteSettings->socialLinks() as $key => $url)
                        @if(filled($url))<a href="{{ $url }}" target="_blank" rel="noopener" class="rounded-full bg-slate-800 px-3 py-1 text-xs font-medium transition hover:bg-slate-700 hover:text-white">{{ $socialLabels[$key] ?? $key }}</a>@endif
                    @endforeach
                </div>
            @endif
        </div>
        <div>
            <p class="font-bold text-white">روابط سريعة</p>
            <div class="mt-4 grid grid-cols-2 gap-2 text-sm">
                @unless($siteSettings->isSectionHidden('biography'))
                    <a class="transition hover:text-white" href="{{ route('biography.show') }}">السيرة الذاتية</a>
                @endunless
                @unless($siteSettings->isSectionHidden('quran-centrality'))
                    <a class="transition hover:text-white" href="{{ route('quran-centrality.index') }}">مركزية القرآن</a>
                @endunless
                @unless($siteSettings->isSectionHidden('quraniyat'))
                    <a class="transition hover:text-white" href="{{ route('quraniyat.index') }}">قرآنيات</a>
                @endunless
                @unless($siteSettings->isSectionHidden('wall'))
                    <a class="transition hover:text-white" href="{{ route('wall.index') }}">حائط</a>
                @endunless
                @unless($siteSettings->isSectionHidden('reflections'))
                    <a class="transition hover:text-white" href="{{ route('reflections.index') }}">تأملات</a>
                @endunless
                @unless($siteSettings->isSectionHidden('books'))
                    <a class="transition hover:text-white" href="{{ route('books.index') }}">الكتب</a>
                @endunless
                @unless($siteSettings->isSectionHidden('programs'))
                    <a class="transition hover:text-white" href="{{ route('programs.index') }}">البرامج</a>
                @endunless
                @unless($siteSettings->isSectionHidden('lectures'))
                    <a class="transition hover:text-white" href="{{ route('lectures.index') }}">المحاضرات</a>
                @endunless
            </div>
        </div>
        <div>
            <p class="font-bold text-white">البحث</p>
            <form action="{{ route('search') }}" class="mt-4 flex gap-2">
                <input name="q" aria-label="كلمة البحث" class="min-w-0 flex-1 rounded-xl border-0 bg-slate-800 px-4 py-2.5 text-sm text-white placeholder:text-slate-500" placeholder="كلمة البحث">
                <button class="rounded-xl bg-emerald-600 px-4 text-sm font-semibold transition hover:bg-emerald-700">بحث</button>
            </form>
        </div>
    </div>
    <div class="border-t border-slate-800 py-6 text-center text-xs text-slate-500">© {{ now()->year }} {{ $siteSettings->siteName() }}</div>
</footer>
@endsection
