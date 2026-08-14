@php($trail = $trail ?? [])
@if(count($trail) > 1)
    <nav aria-label="breadcrumb" class="text-sm text-slate-500">
        <ol class="flex flex-wrap items-center gap-1.5">
            @foreach($trail as $index => [$label, $url])
                <li class="flex items-center gap-1.5">
                    @if($url && !$loop->last)
                        <a href="{{ $url }}" class="transition hover:text-emerald-700">{{ $label }}</a>
                    @else
                        <span class="font-medium text-slate-700">{{ $label }}</span>
                    @endif
                    @unless($loop->last)<span class="text-slate-300">/</span>@endunless
                </li>
            @endforeach
        </ol>
    </nav>

    @push('json-ld')
    <script type="application/ld+json">{!! json_encode([
        '@@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => collect($trail)->values()->map(fn ($crumb, $index) => array_filter([
            '@type' => 'ListItem',
            'position' => $index + 1,
            'name' => $crumb[0],
            'item' => $crumb[1],
        ]))->all(),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
    @endpush
@endif
