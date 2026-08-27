{{--
    Renders plain-text body content (as stored — a plain <textarea>, no rich
    editor) as properly separated paragraphs instead of one block riddled
    with <br>: a blank line becomes a paragraph break, a single line break
    within a paragraph stays a <br>. Expects: $text.
--}}
@php($paragraphs = preg_split('/\n\s*\n/u', trim((string) ($text ?? ''))))
@foreach($paragraphs as $paragraph)
    @continue(trim($paragraph) === '')
    <p class="mb-5 last:mb-0">{!! nl2br(e(trim($paragraph))) !!}</p>
@endforeach
