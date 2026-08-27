{{--
    Compact internal "page hero" used at the top of every public listing
    page (books, lectures, programs, reflections, quraniyat, quran-centrality,
    wall). Keeps the same dark-emerald identity as the homepage hero but far
    shorter, so it reads as a section header rather than a second homepage.

    Expects: $heroIcon (raw HTML, trusted — always a literal string from the
    calling view, never user input), $heroTitle, $heroDescription.
--}}
<section class="relative overflow-hidden bg-brand-gradient-deep text-white">
    <div class="pointer-events-none absolute inset-0 bg-dot-grid-26 opacity-[0.05]"></div>
    <div class="pointer-events-none absolute -top-16 -right-10 h-56 w-56 rounded-full bg-emerald-600/20 blur-3xl"></div>

    <div class="relative mx-auto max-w-6xl px-5 py-10 sm:py-12 lg:px-8">
        <div class="flex items-center gap-4">
            <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-white/10 text-amber-300 ring-1 ring-white/15 sm:h-14 sm:w-14 [&_svg]:h-6 [&_svg]:w-6 sm:[&_svg]:h-7 sm:[&_svg]:w-7 text-2xl sm:text-3xl">
                {!! $heroIcon !!}
            </span>
            <div class="min-w-0">
                <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">{{ $heroTitle }}</h1>
                @if($heroDescription ?? null)<p class="mt-1 max-w-2xl text-sm leading-6 text-emerald-100/80 sm:text-base">{{ $heroDescription }}</p>@endif
            </div>
        </div>
    </div>

    <svg class="relative -mb-px block h-8 w-full text-slate-50" viewBox="0 0 1200 32" preserveAspectRatio="none" fill="currentColor"><path d="M0 32 C300 4 900 4 1200 32 Z"></path></svg>
</section>
