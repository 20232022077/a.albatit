{{--
    Small share icon + menu, dropped into any content card or show-page
    header without changing its existing layout. Expects: $shareTitle,
    $shareUrl (the content's own title/URL — never generates a new link),
    $inline (optional, default false: false = absolutely-positioned corner
    badge for a card's outer <a>; true = a normal inline icon for a show
    page's header row, next to the title/meta info), $dark (optional,
    default false — the biography hero's own gradient is the one dark
    background this sits on; swaps the inline button to the same
    translucent-white treatment its own social-link buttons already use).

    The menu itself is `position: fixed`, not `absolute` — every card this
    sits inside already has both `overflow-hidden` and a `hover:-translate-y-2`
    transform (which creates a new containing block for fixed descendants
    while hovered), so app.js moves the menu to a direct child of <body>
    the first time it opens and positions it from the trigger's own
    on-screen coordinates. That's also why the trigger and the option
    buttons are all <button>, never <a> — this partial is often rendered
    inside a card that is itself one big <a>, and a nested/portaled <a>
    inside another <a> behaves unpredictably across browsers.
--}}
@php
    $shareId = 'share-' . \Illuminate\Support\Str::random(10);
    $inline = $inline ?? false;
    $dark = $dark ?? false;
    $buttonClasses = match(true) {
        $dark => 'border border-amber-300/30 bg-white/10 text-white hover:bg-amber-400/20',
        $inline => 'border border-slate-200 bg-white text-slate-500 hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700',
        default => 'bg-white/90 text-emerald-700 shadow-sm backdrop-blur-sm hover:bg-white hover:text-emerald-900',
    };
@endphp
<div class="{{ $inline ? 'relative inline-flex align-middle' : 'absolute end-2 top-2 z-20' }}" data-share-widget>
    <button
        type="button"
        class="flex h-8 w-8 items-center justify-center rounded-full transition focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-600 {{ $buttonClasses }}"
        data-share-trigger
        data-share-target="{{ $shareId }}"
        data-share-title="{{ $shareTitle }}"
        data-share-url="{{ $shareUrl }}"
        aria-haspopup="true"
        aria-expanded="false"
        aria-label="مشاركة"
    ><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="18" cy="5" r="2.6"/><circle cx="6" cy="12" r="2.6"/><circle cx="18" cy="19" r="2.6"/><line x1="8.3" y1="10.6" x2="15.7" y2="6.6"/><line x1="8.3" y1="13.4" x2="15.7" y2="17.4"/></svg></button>

    <div id="{{ $shareId }}" class="fixed z-50 hidden w-48 rounded-xl border border-slate-200 bg-white py-1.5 shadow-xl" data-share-menu data-share-title="{{ $shareTitle }}" data-share-url="{{ $shareUrl }}" role="menu">
        <button type="button" data-share-option="whatsapp" class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-slate-700 transition hover:bg-emerald-50" role="menuitem"><svg class="h-4 w-4 shrink-0 text-[#25D366]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm5.6 14.3c-.2.7-1.4 1.3-2 1.4-.5.1-1.2.2-3.6-.8-3-1.2-4.9-4.3-5.1-4.5-.1-.2-1.2-1.6-1.2-3s.7-2.1 1-2.4c.3-.3.6-.3.8-.3h.6c.2 0 .4 0 .6.5.2.5.8 1.9.8 2 .1.2.1.3 0 .5-.1.2-.1.3-.3.5l-.4.5c-.1.2-.3.3-.1.6.2.3.9 1.4 1.8 2.3 1.3 1.2 2.4 1.6 2.7 1.7.3.1.5.1.6-.1l.9-1c.2-.3.5-.2.8-.1l1.8.9c.2.1.4.2.5.3.1.2.1.9-.1 1.6Z"/></svg>واتساب</button>
        <button type="button" data-share-option="facebook" class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-slate-700 transition hover:bg-emerald-50" role="menuitem"><svg class="h-4 w-4 shrink-0 text-[#1877F2]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M14 3h-2.5C9 3 7.5 4.6 7.5 7.2V10H5v3.5h2.5V21H11v-7.5h2.7l.5-3.5h-3.2V7.5c0-1 .3-1.7 1.7-1.7H14V3Z"/></svg>فيسبوك</button>
        <button type="button" data-share-option="x" class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-slate-700 transition hover:bg-emerald-50" role="menuitem"><svg class="h-4 w-4 shrink-0 text-slate-900" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M4 4l7 8.4L4.4 20H7l5.4-6 4.6 6H20l-7.4-8.9L19.6 4H17l-5 5.7L8 4H4Z"/></svg>X</button>
        <button type="button" data-share-option="telegram" class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-slate-700 transition hover:bg-emerald-50" role="menuitem"><svg class="h-4 w-4 shrink-0 text-[#26A5E4]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M21 4 3 11.3c-.9.4-.9 1.6.1 1.9l4.4 1.4 1.7 5.3c.3.9 1.4 1.1 2 .4l2.4-2.6 4.5 3.3c.7.5 1.7.1 1.9-.7L23 5.3c.2-1-.7-1.7-1.6-1.3ZM8.9 14.3l8.6-6.9c.2-.1.4.1.2.3l-7.1 7.2-.3 3-1.4-3.6Z"/></svg>تيليجرام</button>
        <button type="button" data-share-option="email" class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-slate-700 transition hover:bg-emerald-50" role="menuitem"><svg class="h-4 w-4 shrink-0 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3.5 6 8.5 7 8.5-7"/></svg>البريد الإلكتروني</button>
        <div class="my-1 border-t border-slate-100"></div>
        <button type="button" data-share-option="copy" class="flex w-full items-center gap-2.5 px-3.5 py-2 text-sm text-slate-700 transition hover:bg-emerald-50" role="menuitem"><svg class="h-4 w-4 shrink-0 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9.5 14.5 14.5 9.5"/><path d="M11 6.5l1-1a3.5 3.5 0 0 1 5 5l-1 1"/><path d="M13 17.5l-1 1a3.5 3.5 0 0 1-5-5l1-1"/></svg><span data-share-copy-label>نسخ الرابط</span></button>
    </div>
</div>
