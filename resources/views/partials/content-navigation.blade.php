@if(($prev ?? null) || ($next ?? null))
    <nav class="mx-auto mt-10 grid max-w-3xl grid-cols-1 gap-4 sm:grid-cols-2" aria-label="التنقل بين المحتوى">
        @if($prev ?? null)
            <a href="{{ \App\Support\ContentUrl::for($prev) }}" class="group flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-md">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-emerald-50 text-emerald-700 transition group-hover:bg-emerald-700 group-hover:text-white">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 7l5 5-5 5M19 12H5"/></svg>
                </span>
                <span class="min-w-0">
                    <span class="block text-xs font-bold text-slate-400">السابق</span>
                    <span class="mt-0.5 block truncate text-sm font-bold text-slate-900">{{ $prev->title }}</span>
                </span>
            </a>
        @else
            <div></div>
        @endif

        @if($next ?? null)
            <a href="{{ \App\Support\ContentUrl::for($next) }}" class="group flex items-center justify-end gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-md">
                <span class="min-w-0 text-right">
                    <span class="block text-xs font-bold text-slate-400">التالي</span>
                    <span class="mt-0.5 block truncate text-sm font-bold text-slate-900">{{ $next->title }}</span>
                </span>
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-emerald-50 text-emerald-700 transition group-hover:bg-emerald-700 group-hover:text-white">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 17l-5-5 5-5M5 12h14"/></svg>
                </span>
            </a>
        @endif
    </nav>
@endif
