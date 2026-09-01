{{--
    Shared section-heading treatment used across the homepage and index
    pages so every section reads as "the same family" while still getting
    its own accent color and icon: a colored icon badge beside the title
    plus a short gradient underline beneath it — distinctive without an
    extra label line competing for attention. Tailwind's class scanner
    needs literal class strings in the source, so the accent is resolved
    to a full, hardcoded class string here rather than interpolated.

    Expects: $title, $icon (raw HTML, optional — a full "<svg>...</svg>" tag
    or a plain glyph like "✦"/"۞", exactly like page-hero's own $heroIcon, so
    a section's home-page badge can share the identical icon markup its own
    page-hero uses instead of drifting into a second, slightly different
    icon), $accent ('emerald'|'amber'|'slate', optional, default 'emerald'),
    $action (optional ['label' => ..., 'url' => ...] "view all" link).
--}}
@php
    $accentClasses = match($accent ?? 'emerald') {
        'amber' => ['badge' => 'bg-amber-50 text-amber-700', 'bar' => 'from-amber-400 to-amber-200', 'button' => 'bg-amber-50 text-amber-800 ring-amber-100 hover:bg-amber-800 hover:text-white hover:ring-amber-800'],
        'slate' => ['badge' => 'bg-slate-100 text-slate-600', 'bar' => 'from-slate-400 to-slate-200', 'button' => 'bg-slate-100 text-slate-700 ring-slate-200 hover:bg-slate-800 hover:text-white hover:ring-slate-800'],
        default => ['badge' => 'bg-emerald-50 text-emerald-700', 'bar' => 'from-emerald-500 to-emerald-200', 'button' => 'bg-emerald-50 text-emerald-800 ring-emerald-100 hover:bg-emerald-800 hover:text-white hover:ring-emerald-800'],
    };
@endphp
<div class="flex flex-wrap items-center justify-between gap-4">
    <div class="flex items-center gap-3.5">
        @if($icon ?? null)
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl {{ $accentClasses['badge'] }} [&_svg]:h-5 [&_svg]:w-5 text-xl">
                {!! $icon !!}
            </span>
        @endif
        <div>
            <h2 class="text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">{{ $title }}</h2>
            <span class="mt-1.5 block h-1 w-12 rounded-full bg-gradient-to-l {{ $accentClasses['bar'] }}"></span>
        </div>
    </div>
    @if($action ?? null)
        <a href="{{ $action['url'] }}" class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-4 py-2 text-sm font-bold ring-1 transition {{ $accentClasses['button'] }}">
            {{ $action['label'] }}
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 7l5 5-5 5M19 12H5"/></svg>
        </a>
    @endif
</div>
