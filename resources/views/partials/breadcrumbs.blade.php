@php($trail = $trail ?? [])
@if(count($trail) > 1)
    <nav aria-label="breadcrumb" class="text-sm text-slate-500">
        <ol class="flex flex-wrap items-center gap-1.5" itemscope itemtype="https://schema.org/BreadcrumbList">
            @foreach($trail as $index => [$label, $url])
                <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem" class="flex items-center gap-1.5">
                    @if($url && !$loop->last)
                        <a href="{{ $url }}" itemprop="item" class="transition hover:text-emerald-700"><span itemprop="name">{{ $label }}</span></a>
                    @else
                        <span itemprop="name" class="font-medium text-slate-700">{{ $label }}</span>
                    @endif
                    <meta itemprop="position" content="{{ $index + 1 }}">
                    @unless($loop->last)<span class="text-slate-300">/</span>@endunless
                </li>
            @endforeach
        </ol>
    </nav>
@endif
