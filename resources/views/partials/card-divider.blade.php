{{--
    Small ornamental divider used inside content cards to separate the
    title from its body snippet — a diamond flanked by a solid accent bar
    and a fading tail, echoing the geometric accents already used across
    the site (section headings' gradient underline, quraniyat's arch icon)
    instead of a plain rule. Expects: $accent ('amber'|'emerald'|'slate').
--}}
@php
    $tone = match($accent ?? 'amber') {
        'slate' => ['bar' => 'bg-slate-400', 'fade' => 'from-slate-400', 'dot' => 'bg-slate-500'],
        'emerald' => ['bar' => 'bg-emerald-500', 'fade' => 'from-emerald-500', 'dot' => 'bg-emerald-600'],
        default => ['bar' => 'bg-amber-400', 'fade' => 'from-amber-400', 'dot' => 'bg-amber-500'],
    };
@endphp
<div class="my-3 flex items-center gap-2" aria-hidden="true">
    <span class="h-[3px] w-7 shrink-0 rounded-full {{ $tone['bar'] }}"></span>
    <span class="h-2.5 w-2.5 shrink-0 rotate-45 rounded-[2px] {{ $tone['dot'] }}"></span>
    <span class="h-[3px] flex-1 rounded-full bg-gradient-to-l {{ $tone['fade'] }} to-transparent"></span>
</div>
