{{--
    Shared section-heading treatment used across the homepage and index
    pages so every section reads as "the same family" while still getting
    its own accent color and icon: a colored icon badge beside the title
    plus a short gradient underline beneath it — distinctive without an
    extra label line competing for attention. Tailwind's class scanner
    needs literal class strings in the source, so the accent is resolved
    to a full, hardcoded class string here rather than interpolated.

    Expects: $title, $icon (raw svg path/shape markup, optional), $accent
    ('emerald'|'amber'|'slate', optional, default 'emerald'), $action
    (optional ['label' => ..., 'url' => ...] "view all" link).
--}}
@php
    $accentClasses = match($accent ?? 'emerald') {
        'amber' => ['badge' => 'bg-amber-50 text-amber-700', 'bar' => 'from-amber-400 to-amber-200', 'link' => 'text-amber-700 hover:text-amber-900'],
        'slate' => ['badge' => 'bg-slate-100 text-slate-600', 'bar' => 'from-slate-400 to-slate-200', 'link' => 'text-slate-700 hover:text-slate-900'],
        default => ['badge' => 'bg-emerald-50 text-emerald-700', 'bar' => 'from-emerald-500 to-emerald-200', 'link' => 'text-emerald-700 hover:text-emerald-900'],
    };
@endphp
<div class="flex flex-wrap items-center justify-between gap-4">
    <div class="flex items-center gap-3.5">
        @if($icon ?? null)
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl {{ $accentClasses['badge'] }} [&_svg]:h-5 [&_svg]:w-5">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $icon !!}</svg>
            </span>
        @endif
        <div>
            <h2 class="text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">{{ $title }}</h2>
            <span class="mt-1.5 block h-1 w-12 rounded-full bg-gradient-to-l {{ $accentClasses['bar'] }}"></span>
        </div>
    </div>
    @if($action ?? null)
        <a href="{{ $action['url'] }}" class="hidden shrink-0 items-center gap-1.5 text-sm font-bold {{ $accentClasses['link'] }} transition sm:flex">
            {{ $action['label'] }}
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 7l5 5-5 5M19 12H5"/></svg>
        </a>
    @endif
</div>
