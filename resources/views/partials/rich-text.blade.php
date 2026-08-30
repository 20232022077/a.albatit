{{--
    Renders plain-text body content (as stored — a plain <textarea>, no rich
    editor) as properly separated paragraphs instead of one block riddled
    with <br>: a blank line becomes a paragraph break, a single line break
    within a paragraph stays a <br>. Any inline-quoted Qur'anic verse is
    auto-detected and rendered in its own typeface/color (see
    App\Support\QuranTextHighlighter). Expects: $text, $accent (optional,
    'emerald'|'amber'|'slate', default 'emerald' — matches whichever accent
    the calling page's own cards/dividers already use).
--}}
@php
    // Normalize CRLF/CR to LF before splitting — admin content pasted from
    // Windows editors often carries \r\n, and \n\s*\n alone can silently
    // fail to find a single blank-line paragraph break in that text (the
    // whole body then renders as one paragraph, which also throws off
    // QuranTextHighlighter's line-by-line verse/citation matching since
    // unrelated paragraphs end up sharing one "line").
    $normalized = \App\Support\QuranTextHighlighter::stripFontArtifacts(
        str_replace(["\r\n", "\r"], "\n", trim((string) ($text ?? '')))
    );
    $rawParagraphs = array_values(array_filter(
        preg_split('/\n\s*\n/u', $normalized),
        fn (string $p) => trim($p) !== ''
    ));
    $htmlParagraphs = array_map(fn (string $p) => nl2br(e(trim($p))), $rawParagraphs);
    // highlightParagraphs() (not a plain highlight() per paragraph) so a
    // verse quoted as a plain standalone opening line can still be matched
    // against its properly-marked, cited restatement even when that's in a
    // different paragraph — see QuranTextHighlighter::backfillPlainQuotes().
    $highlighted = \App\Support\QuranTextHighlighter::highlightParagraphs($htmlParagraphs, $accent ?? 'emerald');
@endphp
@foreach($highlighted as $paragraphHtml)
    <p class="mb-5 last:mb-0">{!! $paragraphHtml !!}</p>
@endforeach
