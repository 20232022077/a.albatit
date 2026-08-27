{{--
    Full-width ornamental break used to join two stacked blocks inside the
    same card (e.g. an item's header and its body) into one visual unit,
    instead of a text label and a gap between two separate boxes. A single
    diamond with lines fading in from both edges toward the center — the
    same geometric language as partials.card-divider, just symmetric and
    edge-to-edge. Expects: $accent ('amber'|'emerald'|'slate', optional).
--}}
@php
    $tone = match($accent ?? 'amber') {
        'slate' => ['dot' => 'bg-slate-400', 'fade' => 'via-slate-400/70'],
        'emerald' => ['dot' => 'bg-emerald-500', 'fade' => 'via-emerald-500/70'],
        default => ['dot' => 'bg-amber-400', 'fade' => 'via-amber-400/70'],
    };
@endphp
<div class="flex items-center gap-3 px-6 sm:px-8" aria-hidden="true">
    <span class="h-px flex-1 bg-gradient-to-l from-transparent {{ $tone['fade'] }} to-transparent"></span>
    <span class="h-2.5 w-2.5 shrink-0 rotate-45 rounded-[2px] {{ $tone['dot'] }}"></span>
    <span class="h-px flex-1 bg-gradient-to-r from-transparent {{ $tone['fade'] }} to-transparent"></span>
</div>
