@php($src = \App\Support\SafeYoutube::embedUrl($videoId ?? ''))
@if($src)
    <div class="{{ $class ?? 'aspect-video overflow-hidden rounded-2xl bg-black' }}">
        <iframe
            src="{{ $src }}"
            class="h-full w-full"
            title="مشغل فيديو يوتيوب"
            loading="lazy"
            referrerpolicy="strict-origin-when-cross-origin"
            sandbox="allow-scripts allow-same-origin allow-presentation allow-popups"
            allow="accelerometer; encrypted-media; gyroscope; picture-in-picture; web-share"
            allowfullscreen
        ></iframe>
    </div>
@endif
