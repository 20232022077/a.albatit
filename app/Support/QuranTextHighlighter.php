<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Wraps Qur'anic verse quotes embedded inline in an admin's own prose
 * (e.g. "...القرآن يقول: إِنَّ فِرۡعَوۡنَ عَلَا فِي ٱلۡأَرۡضِ... ٤ القصص، وهذا يعني...")
 * in <span class="quran-verse quran-verse--{accent}"> — adding the
 * traditional Qur'anic quotation brackets ﴿ ﴾ — so verses render in a
 * dedicated typeface and a tinted color instead of blending into the
 * surrounding commentary. See resources/css/app.css for the typeface
 * rationale.
 *
 * Detection runs in two passes over each line (a <br />-delimited chunk
 * of the body text):
 *
 *  1. Citation-anchored: this content usually follows a quoted verse with
 *     a "٤ القصص:" / "هود:" style reference (surah name, optionally a
 *     leading Arabic-Indic verse number, then a colon — the number is
 *     often left out in practice, so it's optional here). That citation
 *     is a far more reliable end-of-verse boundary than guessing from
 *     where diacritic marks stop appearing.
 *
 *  2. Fallback (word-gap): for verses quoted without a trailing citation,
 *     or for the portion before the first citation on a line, consecutive
 *     Qur'an-marked words — tolerating a handful of unmarked words
 *     between them — are merged into one run.
 *
 * In both passes, a short run of unmarked leading words (a connector like
 * "إِنَّ"/"وَ" that's grammatically part of the verse, or a short
 * "وقال:"-style lead-in that isn't) is folded into the run if it's close
 * enough to the first marked word — this occasionally sweeps in a stray
 * lead-in word, but that's a far smaller visual error than the opposite:
 * silently cutting off the verse's own opening word.
 *
 * Marker characters: Uthmani/Mushaf-script Qur'an text uses several
 * Unicode combining marks that essentially never appear in ordinary
 * Arabic prose — the elidable-hamza alef (ٱ), the small-vowel and
 * maddah/hamza marks in U+0653-U+065F, the Qur'an-only "small high"
 * letters and waqf/pause signs, and the Qur'anic annotation block. This
 * is a heuristic, not a real Qur'an-text match, so a boundary can still
 * occasionally land a word early or late; it never fires on ordinary
 * prose, since that needs a genuinely dense run of these marks to
 * trigger at all — no manual tagging by admins required.
 *
 * Explicit marking: an admin who wants zero guessing at all — e.g. a verse
 * pasted straight out of Word, already set in the same Uthmani font the
 * site uses — can wrap it themselves in double square brackets:
 * "[[فَبِأَيِّ ءَالَآءِ رَبِّكُمَا تُكَذِّبَانِ]] الرحمن :". The bracketed text is
 * wrapped verbatim, byte-for-byte, with none of the marker-density or
 * word-gap logic above ever looking at it; a citation immediately after
 * the closing "]]" is still reformatted exactly like the heuristic path
 * does (see highlightExplicitMarkers()). Everything outside "[[...]]"
 * still goes through the normal heuristic detection, so existing content
 * that never used explicit markers keeps working unchanged.
 */
class QuranTextHighlighter
{
    private const MARKER_PATTERN = '/[\x{0653}-\x{065F}\x{0670}\x{0671}\x{06D6}-\x{06ED}\x{08D3}-\x{08FF}]/u';

    /** Qur'an-only *waqf/boundary* marks — the six stop signs (06D6-06DC:
     *  ۖ ۗ ۘ ۙ ۚ ۛ), end-of-ayah (۝), rub-el-hizb (۞), sajda (۩). Deliberate,
     *  rare, and placed at a genuine pause or verse boundary, so a single
     *  one of these is trusted as a much stronger, wider-reaching signal
     *  below (see $maxGap and $groupHasAnchor in runHighlight()) — real
     *  Mushaf text quoted in plain/Imlaei diacritics (rather than full
     *  Uthmani script) often carries none of MARKER_PATTERN's other marks
     *  for most of its words, but always carries one of these.
     *  Deliberately excludes 06DF-06E8 (small high/low silent-letter and
     *  recitation-helper marks, e.g. small high dotless head of khah) even
     *  though those are also in MARKER_PATTERN — unlike the waqf signs,
     *  those appear on a large fraction of ordinary Uthmani-script *words*
     *  (e.g. "مِنۡ" alone carries one), not just at pauses, so treating one
     *  as a boundary would pull in unrelated neighbouring text. */
    private const ANCHOR_PATTERN = '/[\x{06D6}-\x{06DE}\x{06E9}]/u';

    /** A bare number with nothing else in the token — the inline ayah-number
     *  a Mushaf often prints right after ۝ (e.g. "۝ 177"). Never legitimate
     *  as the last word inside a verse bracket: it's excluded from any
     *  wrapped span even when otherwise within reach (see runHighlight()). */
    private const BARE_NUMBER_PATTERN = '/^[٠-٩0-9]+$/u';

    /** Sentence-final punctuation. A word ending in one of these is the last
     *  word of a *different* sentence, so lead-in extension (see
     *  extendStartBack()) must never reach across it into a verse run. */
    private const SENTENCE_END_PATTERN = '/[.؟!]$/u';

    /** A quotation mark anywhere in a token — ASCII, guillemets, or the
     *  "smart quotes" Word's autocorrect turns straight ones into. A word
     *  touching one of these is part of the *author's own* quoted aside
     *  (e.g. "من "يا ليت" إلى قُمۡ", contrasting an idiom with a verse), never
     *  the Qur'an verse itself, so both extendStartBack() and the colon
     *  search below must never cross one. */
    private const QUOTE_PATTERN = '/["«»\x{201C}\x{201D}\x{2018}\x{2019}]/u';

    /** How far back (in words) findPrecedingColonWordIndex() will look for
     *  a colon introducing a fresh quotation. Same value as MAX_GAP purely
     *  because both represent "a reasonable stretch of an author's own
     *  lead-in prose" — not because the two searches are related. */
    private const COLON_LOOKBACK = 6;

    /** "٤ القصص:" / "هود:" / "١٨هود:" / "٧٨ آل عِمۡرَان:" — an optional
     *  Arabic-Indic verse number (glued to the surah name or separated by a
     *  space — admins write both), then the surah name, an optional space,
     *  then a colon. The surah name itself is deliberately only ONE word,
     *  with a single named exception: "آل عمران", the one genuinely
     *  two-word surah name in the Qur'an (matched first, since PCRE
     *  alternation tries left-to-right) — every other two-word pattern here
     *  ("word1 word2:") would happily match "[last word of the verse]
     *  [surah name]:" and swallow the verse's own last word into the
     *  citation instead of the run, which is why the general case stays
     *  single-word-only. Each surah-name alternative excludes digits itself
     *  (via the negative lookahead) so a glued "١٨هود" splits into
     *  number="١٨"/surah="هود" instead of the whole blob landing in the
     *  surah capture. Captures: 1 = verse number (if any), 2 = surah name —
     *  used to re-render the citation in a fixed "(سورة: رقم)" order
     *  regardless of how the admin originally typed it (the raw text is
     *  often "رقم سورة:", reversed from the conventional reading order).
     *  "آل" itself is matched three ways — literal "آ" (precomposed
     *  U+0622), "ا" plus one-or-more combining marks (\p{Mn}+, e.g. U+0653
     *  madda above), or a bare U+0653 with no base "ا" at all — because
     *  real admin-typed text sometimes stores that letter in NFD-decomposed
     *  form rather than the single precomposed character, and only a
     *  byte-identical match would otherwise recognise it. The \p{Mn}+ (not
     *  \p{Mn}*) is required: a bare "ا" immediately followed by "ل" with
     *  zero marks between them is just the ordinary "ال" definite article
     *  prefixing an unrelated single-word surah name (القصص, الكريم, …),
     *  not this decomposed "آ". The bare-U+0653 alternative exists for a
     *  real copy/paste corruption seen in production content: the base "ا"
     *  dropped entirely, leaving an orphaned madda floating right before
     *  "ل" — deliberately narrowed to that one exact mark (not any \p{Mn})
     *  so it can't also swallow an unrelated word that merely ends in some
     *  other diacritic immediately before an unrelated "ل".
     *
     *  A third trailing group (3) captures a number written AFTER the
     *  colon instead — "العَلَق: ١" — the order this font's own citation
     *  ligatures (CITATION_OPEN/CLOSE_ARTIFACT above) produce once their
     *  digit ligatures are recovered to real Arabic-Indic digits, already
     *  in the exact "سورة: رقم" order formatCitation() renders everything
     *  else into. Both number groups are optional and mutually exclusive
     *  in practice (a real citation has the number on one side of the
     *  colon or the other, never both) — callers combine them with
     *  "group 1 if set, else group 3". */
    private const CITATION_PATTERN = '/(?:([٠-٩]+)\s*)?((?:آ|ا\p{Mn}+|\x{0653})ل\s+(?:(?!\p{Nd})\p{Arabic})+|(?:(?!\p{Nd})\p{Arabic})+)\s*:\s*(?:([٠-٩]+))?/u';

    /** Consecutive marked words separated by at most this many unmarked
     *  words are treated as the same verse run. */
    private const MAX_GAP = 6;

    /** Same idea as MAX_GAP, but used for a line that contains at least one
     *  ANCHOR_PATTERN mark — those only show up in genuine Qur'an text, so
     *  once one is seen anywhere on the line, the far larger gaps typical of
     *  plain-diacritic Mushaf quotes (long stretches of ordinary-looking
     *  words between the rare Qur'an-only pause marks) are trusted too. */
    private const ANCHOR_MAX_GAP = 60;

    /** How far back (in words) a run's start may reach past its first
     *  marked word — short, since this is only meant to catch a verse's
     *  own opening connective, not swallow the author's lead-in prose. A
     *  verse's own genuine opening word occasionally carries no marker
     *  itself and needs more than a 1-word reach (e.g. "إِنِّي" before
     *  "إِلَىٰ" in "إِنِّي ذَاهِبٌ إِلَىٰ رَبِّي") — but widening this constant
     *  itself to fix that turned out to also reach back across an
     *  unrelated quoted aside elsewhere in the same sentence (e.g. "من "يا
     *  لَيْتَ" إلى قُمۡ" started swallowing "لَيْتَ" too). The real, narrower
     *  fix lives in findPrecedingColonWordIndex(): a colon within reach is
     *  trusted as a much stronger boundary and can extend the reach
     *  further than this constant alone, since a fresh quotation
     *  conventionally starts right after one — this constant stays at the
     *  original, conservative 1 for everything else. */
    private const LEAD_IN_TOLERANCE = 1;

    /** A run must contain at least this many individually-marked words to
     *  be wrapped — filters out a single incidentally-voweled word. Waived
     *  when every single word in the text being scanned is marked (see
     *  $allWordsMarked in runHighlight()): a lone word like "ٱقۡرَأۡ" quoted
     *  by itself on its own line is still unambiguously a verse. Also
     *  waived for the last group when a genuine citation immediately
     *  follows it (see $extendLastRunToEnd in runHighlight()) — a real
     *  "سورة: رقم"-shaped citation right after the text is strong enough
     *  evidence on its own that a short verse with only one marked word
     *  (some verses, by their own phonetic makeup, simply never carry a
     *  second marker — e.g. "فَبِأَيِّ ءَالَآءِ رَبِّكُمَا تُكَذِّبَانِ" has no
     *  sukun letter or wasla-alif anywhere in it, in any correctly-typed
     *  Uthmani rendering) is still a real verse rather than incidental
     *  prose. Scoped to the *last* group only, not every group in a long
     *  stretch of "between" text, so an unrelated marked word earlier in
     *  ordinary prose ahead of some unrelated colon doesn't also qualify. */
    private const MIN_MARKED_WORDS = 2;

    /** "[[...]]" — an admin-drawn boundary around a verse they want wrapped
     *  exactly as typed/pasted, no heuristics involved at all. Non-greedy
     *  so back-to-back markers on one line each close at their own "]]"
     *  rather than one marker swallowing up to the last one on the line. */
    private const EXPLICIT_MARKER_PATTERN = '/\[\[(.+?)\]\]/su';

    /** Some Quran-typesetting fonts (Word's "KFGQPC HAFS Uthmanic Script"
     *  among them) draw decorative brackets — or other ornamentation — by
     *  repurposing obscure legacy Unicode "Arabic Presentation Forms"
     *  ligature/positional-form characters (U+FB50-FDEF, U+FE70-FEFF) as a
     *  private glyph slot, rather than the real portable ornate-parenthesis
     *  characters. That glyph only exists inside that one font's own table
     *  — copy the text out into any other font (this site's included) and
     *  the *real* Unicode meaning of that codepoint renders instead, which
     *  is essentially always garbled, unrelated-looking text. Stripped
     *  entirely in stripFontArtifacts() before any detection runs, so a
     *  verse pasted with one of these "brackets" around it degrades
     *  gracefully to a plain, correctly-detectable quote instead of
     *  garbage — this class's own OPEN_BRACKET/CLOSE_BRACKET (U+FD3E/FD3F,
     *  which sit inside that same range) and the small set of religious-
     *  phrase ligatures at U+FDF0-FDFF (ﷺ ﷽ …), which admins do
     *  legitimately type on purpose, are carved out and left untouched. */
    private const FONT_ARTIFACT_PATTERN = '/[\x{FB50}-\x{FD3D}\x{FD40}-\x{FDEF}\x{FE70}-\x{FEFF}]/u';

    /** This same font's own opening/closing verse-boundary ligatures —
     *  drawn when an admin uses the font's own "wrap in Qur'an brackets"
     *  keyboard shortcut in Word. Unlike every other artifact above, these
     *  are converted (in stripFontArtifacts()) into this class's own
     *  EXPLICIT_MARKER_PATTERN syntax rather than simply discarded: a real
     *  production regression hit five separate articles where the
     *  diacritic-density heuristic got the boundary wrong after the old
     *  strip-then-reguess approach threw this information away — either
     *  swallowing an unrelated aside/citation into the run, or failing to
     *  detect a verse at all because its specific wording happens to carry
     *  none of MARKER_PATTERN's marks (e.g. "فَبِأَيِّ ءَالَآءِ رَبِّكُمَا
     *  تُكَذِّبَانِ") — while the font's own bracket, placed by a human at the
     *  true edge, was correct in every single case. */
    private const VERSE_OPEN_ARTIFACT = "\u{FD5F}";

    private const VERSE_CLOSE_ARTIFACT = "\u{FD5E}";

    /** Same font's citation-boundary ligatures, wrapping the "سورة: رقم"
     *  reference that follows a verse. The opening one is sometimes typed
     *  with zero whitespace before it — glued directly onto the verse-close
     *  ligature above, e.g. "...بِٱلۡمَعۡرُوفِۚ" immediately followed by this
     *  with no space — which, in a real production regression, fused the
     *  surah name onto the verse's own last word into one unsplittable
     *  token once both ligatures were simply deleted. Replaced with a real
     *  space instead of nothing (see stripFontArtifacts()) so a word
     *  boundary always exists before the citation regardless of how the
     *  admin happened to type it. */
    private const CITATION_OPEN_ARTIFACT = "\u{FD5D}";

    private const CITATION_CLOSE_ARTIFACT = "\u{FD5C}";

    /** This same font's own digit ligatures — verse numbers inside a
     *  citation (e.g. "العَلَق: ١") aren't typed as real Arabic-Indic
     *  digits at all when using this font's Qur'an-typing shortcuts; each
     *  digit 0-9 is its own private glyph in this same ligature range,
     *  U+FD50 ("٠") through U+FD59 ("٩") — confirmed by rendering every
     *  glyph in the font and comparing shapes one-by-one against the real
     *  digits. Left alone, FONT_ARTIFACT_PATTERN below deletes them as
     *  decorative noise like everything else in this range, which is
     *  exactly why a citation's verse number was vanishing while the
     *  surah name (ordinary Arabic letters, untouched by that pattern)
     *  kept showing — CITATION_PATTERN's number group had nothing left to
     *  capture. Converted to real digits in stripFontArtifacts() before
     *  that generic strip runs, the same way the bracket ligatures above
     *  are converted rather than discarded. */
    private const DIGIT_LIGATURE_FIRST = 0xFD50;

    /** ARABIC SMALL LOW MEEM — a genuine, if rare, Uthmani recitation
     *  annotation mark, but one this site's Qur'an webfont has no dedicated
     *  glyph for, so it renders as a generic fallback dot/circle that reads
     *  as a display glitch rather than typography. Stripped from display by
     *  explicit editorial request, independent of any boundary detection. */
    private const DISPLAY_ONLY_STRIP_PATTERN = '/\x{06ED}/u';

    private const VALID_ACCENTS = ['emerald', 'amber', 'slate'];

    private const OPEN_BRACKET = '﴿';

    private const CLOSE_BRACKET = '﴾';

    /**
     * Expects already-HTML-escaped text (e.g. nl2br(e($body))) — the only
     * markup present must be the <br /> tags nl2br() inserts, since this
     * only ever wraps runs of the text between them, never touching real
     * HTML tags or attributes.
     */
    public static function highlight(string $html, string $accent = 'emerald'): string
    {
        $accent = in_array($accent, self::VALID_ACCENTS, true) ? $accent : 'emerald';
        $result = self::highlightSingle($html, $accent);

        return self::backfillPlainQuotes($result, $accent, self::extractConfirmedVerses($result));
    }

    /**
     * For a full article body already split into paragraphs (one string
     * per <p>, each already nl2br()'d and HTML-escaped): highlights every
     * paragraph independently first, exactly like highlight() does, but
     * then backfills a plain-tashkeel "epigraph" line (see
     * backfillPlainQuotes()) using verses confirmed ANYWHERE in the whole
     * body — not just its own paragraph. A verse's plain standalone
     * opening line and its properly Uthmani-marked, cited restatement
     * later in the body are frequently in different paragraphs (split
     * apart by a blank line in the source), which each become their own
     * independent highlight() call — so confirming the wording has to
     * look across all of them, not just the one the candidate line is in.
     *
     * @param  string[]  $htmlParagraphs
     * @return string[] same length/order as $htmlParagraphs
     */
    public static function highlightParagraphs(array $htmlParagraphs, string $accent = 'emerald'): array
    {
        $accent = in_array($accent, self::VALID_ACCENTS, true) ? $accent : 'emerald';

        $firstPass = array_map(fn (string $html) => self::highlightSingle($html, $accent), $htmlParagraphs);
        $confirmed = self::extractConfirmedVerses(implode('', $firstPass));

        return array_map(fn (string $html) => self::backfillPlainQuotes($html, $accent, $confirmed), $firstPass);
    }

    private static function highlightSingle(string $html, string $accent): string
    {
        $segments = preg_split('/(<br\s*\/?>)/i', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($segments === false) {
            return $html;
        }

        return implode('', array_map(
            fn (string $segment) => str_starts_with($segment, '<br')
                ? $segment
                : self::highlightLine($segment, $accent),
            $segments
        ));
    }

    /**
     * @return string[] diacritic-stripped inner text of every already-wrapped
     *                  quran-verse span found in $html
     */
    private static function extractConfirmedVerses(string $html): array
    {
        if (preg_match_all('/<span class="quran-verse[^"]*">(.*?)<\/span>/u', $html, $matches) === 0) {
            return [];
        }

        return array_map(
            fn (string $inner) => self::stripDiacritics(trim($inner, self::OPEN_BRACKET.self::CLOSE_BRACKET)),
            $matches[1]
        );
    }

    /**
     * A verse is sometimes also quoted as a short standalone opening line
     * (a "chapter epigraph") typed in plain tashkeel with none of
     * MARKER_PATTERN's marks — e.g. "لِمَنْ خَافَ مَقَامِي وَخَافَ وَعِيدِ" uses only
     * ordinary fatha/kasra/sukun, nothing Qur'an-exclusive, so nothing above
     * can tell it apart from ordinary voweled prose on its own. But the same
     * wording (diacritics aside) is often *also* quoted properly elsewhere
     * in the same body — in Uthmani script, with a citation — where the
     * marker-based pass above already confirmed it (collected by the
     * caller via extractConfirmedVerses(), across the whole body). That's
     * strong enough evidence to wrap the plain copy too, without needing
     * any Unicode signal from the plain copy itself. Deliberately narrow:
     * only an entirely unwrapped, short (≤10-word) line, and only an exact
     * diacritic-stripped substring match against a $confirmed entry — long
     * enough that an unrelated line matching by coincidence is effectively
     * impossible.
     */
    private static function backfillPlainQuotes(string $html, string $accent, array $confirmed): string
    {
        if ($confirmed === []) {
            return $html;
        }

        $segments = preg_split('/(<br\s*\/?>)/i', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($segments === false) {
            return $html;
        }

        return implode('', array_map(function (string $segment) use ($confirmed, $accent) {
            if (str_starts_with($segment, '<br') || str_contains($segment, '<span')) {
                return $segment;
            }

            $plain = trim($segment);
            if ($plain === '' || preg_match(self::MARKER_PATTERN, $plain) === 1) {
                return $segment;
            }

            $wordCount = count(preg_split('/\s+/u', $plain, -1, PREG_SPLIT_NO_EMPTY));
            if ($wordCount > 10) {
                return $segment;
            }

            $normalized = self::stripDiacritics($plain);
            if ($normalized === '') {
                return $segment;
            }

            foreach ($confirmed as $verse) {
                if ($verse !== '' && str_contains($verse, $normalized)) {
                    return self::wrap($plain, $accent);
                }
            }

            return $segment;
        }, $segments));
    }

    private static function stripDiacritics(string $text): string
    {
        return preg_replace('/\p{Mn}/u', '', $text) ?? $text;
    }

    /**
     * Removes font-specific decorative-glyph artifacts (see
     * FONT_ARTIFACT_PATTERN) from raw, not-yet-escaped admin-typed text.
     * Called on the raw body before anything else touches it, so a verse
     * pasted with one of these fake "brackets" around it still reaches the
     * detection/wrapping logic below as a clean, plain quote.
     *
     * The verse- and citation-boundary ligatures (VERSE_OPEN_ARTIFACT etc.)
     * are handled first and specially: converted into "[[...]]", not just
     * deleted, so they're picked up as an explicit, no-guessing boundary
     * by highlightExplicitMarkers() later — see those constants' docblocks
     * for why this is more reliable than the diacritic heuristic. Only
     * done when the open/close markers appear in equal numbers; an admin
     * who deleted just one half while editing would otherwise leave a
     * stray, visible "[[" or "]]" in the rendered prose, so an imbalance
     * falls back to plain deletion via FONT_ARTIFACT_PATTERN below instead,
     * exactly like every other artifact in this range.
     */
    public static function stripFontArtifacts(string $text): string
    {
        if (substr_count($text, self::VERSE_OPEN_ARTIFACT) === substr_count($text, self::VERSE_CLOSE_ARTIFACT)) {
            $text = str_replace(self::VERSE_OPEN_ARTIFACT, '[[', $text);
            $text = str_replace(self::VERSE_CLOSE_ARTIFACT, ']]', $text);
        }

        if (substr_count($text, self::CITATION_OPEN_ARTIFACT) === substr_count($text, self::CITATION_CLOSE_ARTIFACT)) {
            $text = preg_replace('/\s*'.self::CITATION_OPEN_ARTIFACT.'\s*/u', ' ', $text) ?? $text;
            $text = str_replace(self::CITATION_CLOSE_ARTIFACT, '', $text);
        }

        $text = preg_replace_callback('/[\x{FD50}-\x{FD59}]/u', function (array $match): string {
            $codepoint = mb_ord($match[0], 'UTF-8');

            return mb_chr(0x0660 + ($codepoint - self::DIGIT_LIGATURE_FIRST), 'UTF-8');
        }, $text) ?? $text;

        $text = preg_replace(self::DISPLAY_ONLY_STRIP_PATTERN, '', $text) ?? $text;

        return preg_replace(self::FONT_ARTIFACT_PATTERN, '', $text) ?? $text;
    }

    /**
     * For a card preview: truncates raw, unescaped body text to $limit
     * characters and highlights whatever verse text survives. A single-word
     * verse quoted on its own line (e.g. "ٱقۡرَأۡ") still needs a <br /> on
     * each side to be recognised as its own line by highlight() — but the
     * blank-line paragraph gaps in the source (\n\n) would otherwise become
     * a double <br /><br />, wasting a whole visible line of a line-clamped
     * card on empty space. Collapsing every run of newlines to one keeps a
     * verse on its own line without that gap.
     */
    public static function highlightExcerpt(string $rawText, int $limit, string $accent = 'emerald'): string
    {
        $accent = in_array($accent, self::VALID_ACCENTS, true) ? $accent : 'emerald';
        $rawText = self::stripFontArtifacts($rawText);

        $truncated = Str::limit($rawText, $limit);
        // /u is required here — without it, PCRE's \R (which matches the
        // raw byte 0x85 as a "NEL" line break) can match a UTF-8 continuation
        // byte inside a multibyte Arabic character and corrupt it.
        $collapsed = preg_replace('/\R+/u', "\n", $truncated) ?? $truncated;
        $displayResult = self::highlightSingle(nl2br(e($collapsed)), $accent);

        // A plain-tashkeel standalone opening line (see backfillPlainQuotes())
        // is confirmed by its own properly Uthmani-marked, cited restatement
        // — but that restatement is often further into the article than the
        // card excerpt's character limit reaches. Detect verses across the
        // FULL raw body (never displayed here, just used as confirmation
        // evidence) so the excerpt can still backfill against it.
        $fullNormalized = preg_replace('/\R+/u', "\n", $rawText) ?? $rawText;
        $fullHighlighted = self::highlightSingle(nl2br(e($fullNormalized)), $accent);
        $confirmed = self::extractConfirmedVerses($fullHighlighted);

        return self::backfillPlainQuotes($displayResult, $accent, $confirmed);
    }

    private static function highlightLine(string $text, string $accent): string
    {
        if (str_contains($text, '[[')) {
            return self::highlightExplicitMarkers($text, $accent);
        }

        return self::highlightHeuristic($text, $accent);
    }

    /**
     * Splits $text on "[[...]]" markers: each one's inner text is wrapped
     * verbatim (see EXPLICIT_MARKER_PATTERN's docblock), and a citation
     * immediately following (only whitespace in between) is consumed and
     * reformatted the same way highlightHeuristic() reformats one after an
     * auto-detected run. Everything else — before, between, and after the
     * markers — is passed to highlightHeuristic() unchanged, so unmarked
     * content on the same line still gets auto-detected as before.
     */
    private static function highlightExplicitMarkers(string $text, string $accent): string
    {
        $out = '';
        $cursor = 0;

        while (preg_match(self::EXPLICIT_MARKER_PATTERN, $text, $m, PREG_OFFSET_CAPTURE, $cursor) === 1) {
            [$whole, $wholeStart] = $m[0];
            $inner = $m[1][0];

            $out .= self::highlightHeuristic(substr($text, $cursor, $wholeStart - $cursor), $accent);
            $out .= self::wrap(trim($inner), $accent);
            $cursor = $wholeStart + strlen($whole);

            $rest = substr($text, $cursor);
            $trimmedRest = ltrim($rest);
            $leadingWs = substr($rest, 0, strlen($rest) - strlen($trimmedRest));
            if (preg_match(self::CITATION_PATTERN, $trimmedRest, $citeMatch, PREG_OFFSET_CAPTURE) === 1
                && $citeMatch[0][1] === 0) {
                $number = $citeMatch[1][0] !== '' ? $citeMatch[1][0] : ($citeMatch[3][0] ?? '');
                $out .= self::formatCitation($number, $citeMatch[2][0]);
                $cursor += strlen($leadingWs) + strlen($citeMatch[0][0]);
            }
        }

        $out .= self::highlightHeuristic(substr($text, $cursor), $accent);

        return $out;
    }

    private static function highlightHeuristic(string $text, string $accent): string
    {
        if (preg_match(self::MARKER_PATTERN, $text) !== 1) {
            return $text;
        }

        $citationCount = preg_match_all(self::CITATION_PATTERN, $text, $matches, PREG_OFFSET_CAPTURE);
        if ($citationCount === false || $citationCount === 0) {
            return self::runHighlight($text, $accent, false);
        }

        $out = '';
        $cursor = 0;
        foreach ($matches[0] as $i => [$citation, $citationStart]) {
            $between = substr($text, $cursor, $citationStart - $cursor);
            // "true": if this range ends in a run of marked words, trust
            // the citation immediately after it and extend that run all
            // the way to meet it, even across a longer unmarked stretch
            // than MAX_GAP would otherwise allow.
            $matchedVerse = false;
            $runOutput = self::runHighlight($between, $accent, true, $matchedVerse);

            // This "citation-shaped" text didn't genuinely follow a
            // detected verse — it was just an ordinary "كلمة أخرى:"-style
            // phrase in the author's own prose (e.g. "قال:", "بثقة:").
            // Leaving $cursor exactly where it was — not advancing past
            // this match, and not emitting anything for it here — lets the
            // *next* match's $between naturally re-absorb this stretch as
            // ordinary text instead of a boundary. Advancing past it
            // anyway would silently cut a real verse's own colon-based
            // lead-in search (see findPrecedingColonWordIndex()) off from
            // a colon that comes before this false match.
            if (! $matchedVerse) {
                continue;
            }

            // Trailing whitespace left over from $between (e.g. the space
            // before "١٨ هود:" in the source) would otherwise double up
            // with formatCitation()'s own leading space.
            $out .= rtrim($runOutput);
            $number = $matches[1][$i][0] !== '' ? $matches[1][$i][0] : ($matches[3][$i][0] ?? '');
            $out .= self::formatCitation($number, $matches[2][$i][0]);
            $cursor = $citationStart + strlen($citation);
        }
        $out .= self::runHighlight(substr($text, $cursor), $accent, false);

        return $out;
    }

    private static function formatCitation(string $number, string $surah): string
    {
        $surah = trim($surah);
        $number = trim($number);
        $reference = $number !== '' ? "{$surah}: {$number}" : $surah;

        return ' <span class="quran-citation">('.$reference.')</span>';
    }

    /**
     * Finds Qur'an-marked word runs within $text and wraps each one.
     * $extendLastRunToEnd: when true (a citation immediately follows
     * $text), the last run's end is pulled all the way to the end of
     * $text regardless of MAX_GAP, since the citation itself is the
     * boundary; when false, the same MAX_GAP tolerance used between
     * words is used for the trailing edge too. $matchedRun is set to
     * true if at least one run met MIN_MARKED_WORDS and was wrapped —
     * the caller uses this to tell a genuine verse from a citation-shaped
     * phrase that happened not to have one.
     */
    private static function runHighlight(string $text, string $accent, bool $extendLastRunToEnd, ?bool &$matchedRun = false): string
    {
        $matchedRun = false;

        $tokens = preg_split('/(\s+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        if ($tokens === false || $tokens === []) {
            return $text;
        }

        $isWhitespace = fn (string $t): bool => trim($t) === '';
        $isMarked = fn (string $t): bool => ! $isWhitespace($t) && preg_match(self::MARKER_PATTERN, $t) === 1;

        $markedTokenIndexes = [];
        $tokenWordIndex = [];
        $wordIndex = -1;
        foreach ($tokens as $i => $t) {
            if (! $isWhitespace($t)) {
                $wordIndex++;
            }
            $tokenWordIndex[$i] = $wordIndex;
            if ($isMarked($t)) {
                $markedTokenIndexes[] = $i;
            }
        }

        if ($markedTokenIndexes === []) {
            return $text;
        }

        $totalWords = $wordIndex + 1;
        $lastTokenIndex = count($tokens) - 1;
        // The last non-whitespace token — extending a run "to the end"
        // should never pull in trailing whitespace before the closing
        // bracket.
        $lastWordTokenIndex = $lastTokenIndex;
        while ($lastWordTokenIndex >= 0 && $isWhitespace($tokens[$lastWordTokenIndex])) {
            $lastWordTokenIndex--;
        }

        // Every single word here is Qur'an-marked (e.g. a lone "ٱقۡرَأۡ" quoted
        // by itself on its own line) — MIN_MARKED_WORDS exists to filter out
        // one incidentally-marked word inside otherwise-ordinary prose, which
        // this isn't.
        $allWordsMarked = count($markedTokenIndexes) === $totalWords;

        $hasAnchor = false;
        foreach ($markedTokenIndexes as $idx) {
            if (preg_match(self::ANCHOR_PATTERN, $tokens[$idx]) === 1) {
                $hasAnchor = true;
                break;
            }
        }
        $maxGap = $hasAnchor ? self::ANCHOR_MAX_GAP : self::MAX_GAP;

        $groups = [[$markedTokenIndexes[0]]];
        for ($k = 1; $k < count($markedTokenIndexes); $k++) {
            $prevIndex = end($groups[count($groups) - 1]);
            $gapWords = $tokenWordIndex[$markedTokenIndexes[$k]] - $tokenWordIndex[$prevIndex];
            if ($gapWords <= $maxGap) {
                $groups[count($groups) - 1][] = $markedTokenIndexes[$k];
            } else {
                $groups[] = [$markedTokenIndexes[$k]];
            }
        }

        $out = '';
        $cursor = 0;
        $lastGroupPos = count($groups) - 1;
        foreach ($groups as $groupPos => $group) {
            $groupHasAnchor = false;
            foreach ($group as $idx) {
                if (preg_match(self::ANCHOR_PATTERN, $tokens[$idx]) === 1) {
                    $groupHasAnchor = true;
                    break;
                }
            }

            // A lone ۝/۞/۩ is as unambiguous a signal as two ordinary marked
            // words — e.g. a short single-ayah line like "فَوَيْلٌ لِلْمُصَلِّينَ ۝ 4"
            // never gets a second marked word to clear MIN_MARKED_WORDS
            // otherwise. Likewise, the *last* group is waived when a real
            // citation immediately follows the whole $text ($extendLastRunToEnd)
            // — see MIN_MARKED_WORDS's docblock.
            $citationBacked = $extendLastRunToEnd && $groupPos === $lastGroupPos;
            if (count($group) < self::MIN_MARKED_WORDS && ! $allWordsMarked && ! $groupHasAnchor && ! $citationBacked) {
                continue;
            }

            // An anchor mark stands in for the whole ayah leading up to it —
            // "فَوَيْلٌ لِلْمُصَلِّينَ ۝" has no other marked word at all, so the
            // 1-word lead-in tolerance used for an ordinary connector word
            // would strand "فَوَيْلٌ" outside the bracket. The sentence-end
            // check inside extendStartBack() is still the real backstop.
            $leadInTolerance = $groupHasAnchor ? self::ANCHOR_MAX_GAP : self::LEAD_IN_TOLERANCE;
            $start = self::extendStartBack($group[0], $cursor, $tokens, $tokenWordIndex, $leadInTolerance);
            $end = end($group);

            if ($groupPos === $lastGroupPos && $lastWordTokenIndex >= 0) {
                $tolerance = $extendLastRunToEnd ? PHP_INT_MAX : $maxGap;
                if ($totalWords - 1 - $tokenWordIndex[$end] <= $tolerance) {
                    $end = $lastWordTokenIndex;
                }
            }

            // Never let a bare inline ayah-number (the "177" a Mushaf prints
            // right after ۝) end up as the last word inside the bracket.
            while ($end > $start && preg_match(self::BARE_NUMBER_PATTERN, trim($tokens[$end])) === 1) {
                $end -= 2;
            }

            for ($i = $cursor; $i < $start; $i++) {
                $out .= $tokens[$i];
            }

            $span = '';
            for ($i = $start; $i <= $end; $i++) {
                $span .= $tokens[$i];
            }
            $out .= self::wrap(trim($span), $accent);
            $matchedRun = true;

            $cursor = $end + 1;
        }
        for ($i = $cursor; $i <= $lastTokenIndex; $i++) {
            $out .= $tokens[$i];
        }

        return $out;
    }

    /**
     * Walks a run's start token index backward by up to $tolerance words
     * (never past $floor, the first token still available to reclaim),
     * so a short grammatical lead-in right before the first marked word
     * — "إِنَّ" before "أَكۡرَمَكُمۡ", say — is folded into the run instead
     * of left outside it. Never crosses a word ending in sentence-final
     * punctuation ("عبادة الأسلاف. بَلۡ نَتَّبِعُ…" must not pull the period-
     * ending "الأسلاف." — the end of the author's own previous sentence —
     * into the quote that follows it), nor a word touching a quotation
     * mark (see QUOTE_PATTERN) — that's the author's own quoted aside, not
     * the verse. findPrecedingColonWordIndex() can widen the reach beyond
     * $tolerance when a colon is found first; see its own docblock.
     */
    private static function extendStartBack(int $tokenIndex, int $floor, array $tokens, array $tokenWordIndex, int $tolerance): int
    {
        $targetWordIndex = max($tokenWordIndex[$tokenIndex] - $tolerance, $tokenWordIndex[$floor] ?? 0);

        // A colon overrides the tolerance-based target entirely rather than
        // just widening it (min()) — for an anchor-widened $tolerance
        // (see ANCHOR_MAX_GAP in runHighlight()) that matters: real
        // production regression: "...أعلن: إِنَّ ٱللَّهَ لَا يُغَيِّرُ ...
        // بِأَنفُسِهِمۡ ۗ" — the terminal waqf mark on the *last* word makes
        // this whole group anchor-tolerant, and a wide tolerance alone
        // would happily reach right past the colon into "لكن القرآن أعلن
        // أول ما أعلن:", the author's own lead-in sentence. The colon is
        // strictly more precise evidence of the true boundary whenever one
        // is found, so it wins outright instead of just being blended in.
        $colonWordIndex = self::findPrecedingColonWordIndex($tokenIndex, $floor, $tokens, $tokenWordIndex);
        if ($colonWordIndex !== null) {
            $targetWordIndex = $colonWordIndex;
        }

        $newStart = $tokenIndex;
        while ($newStart > $floor && $tokenWordIndex[$newStart - 1] >= $targetWordIndex) {
            $candidate = trim($tokens[$newStart - 1]);
            // Whitespace-only tokens (the separators between words) have no
            // letters either — only judge an actual word token here, or
            // every step of this walk would stop immediately on the
            // whitespace right before it.
            if ($candidate !== '') {
                if (preg_match(self::SENTENCE_END_PATTERN, $candidate) === 1) {
                    break;
                }
                if (preg_match(self::QUOTE_PATTERN, $candidate) === 1) {
                    break;
                }
                // A lone "،" left standing between two citations (e.g.
                // "…ٱلۡمَأۡوَىٰ﴾ (النازعات: ٤١) ، وَلِمَنۡ…") is a separator, not a
                // word of the next verse — pulling it in would open the
                // bracket on a comma.
                if (preg_match('/\p{L}/u', $candidate) !== 1) {
                    break;
                }
            }
            $newStart--;
        }

        return $newStart;
    }

    /**
     * Looks up to COLON_LOOKBACK words back from $tokenIndex for a token
     * ending in ":" — a colon conventionally introduces a fresh quotation
     * ("...بثقة: إِنِّي ذَاهِبٌ إِلَىٰ..."), which is stronger evidence than a
     * plain word-count tolerance that everything back to right after it is
     * the verse itself, even when its own opening word(s) carry no marker.
     * Stops the search (returns null) at the same boundaries
     * extendStartBack() itself respects — sentence-end punctuation, a
     * quotation mark, or a no-letter token — so a colon further back than
     * an unrelated quoted aside or a previous sentence is never reached.
     */
    private static function findPrecedingColonWordIndex(int $tokenIndex, int $floor, array $tokens, array $tokenWordIndex): ?int
    {
        $limitWordIndex = max($tokenWordIndex[$tokenIndex] - self::COLON_LOOKBACK, $tokenWordIndex[$floor] ?? 0);

        $i = $tokenIndex;
        while ($i > $floor && $tokenWordIndex[$i - 1] >= $limitWordIndex) {
            $candidate = trim($tokens[$i - 1]);
            if ($candidate !== '') {
                if (str_ends_with($candidate, ':') || str_ends_with($candidate, '：')) {
                    return $tokenWordIndex[$i - 1] + 1;
                }
                if (preg_match(self::SENTENCE_END_PATTERN, $candidate) === 1) {
                    return null;
                }
                if (preg_match(self::QUOTE_PATTERN, $candidate) === 1) {
                    return null;
                }
                if (preg_match('/\p{L}/u', $candidate) !== 1) {
                    return null;
                }
            }
            $i--;
        }

        return null;
    }

    private static function wrap(string $innerText, string $accent): string
    {
        return '<span class="quran-verse quran-verse--'.$accent.'">'.self::OPEN_BRACKET.$innerText.self::CLOSE_BRACKET.'</span>';
    }
}
