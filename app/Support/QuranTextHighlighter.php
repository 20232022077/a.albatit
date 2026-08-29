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
     *  other diacritic immediately before an unrelated "ل". */
    private const CITATION_PATTERN = '/(?:([٠-٩]+)\s*)?((?:آ|ا\p{Mn}+|\x{0653})ل\s+(?:(?!\p{Nd})\p{Arabic})+|(?:(?!\p{Nd})\p{Arabic})+)\s*:/u';

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
     *  own opening connective(s), not swallow the author's lead-in prose.
     *  2 rather than 1: a verse's own genuine opening word occasionally
     *  carries no marker itself (e.g. "إِنِّي" — plain kasra/shadda only)
     *  and only the word *after* it does, so a 1-word reach can strand the
     *  verse's real first word outside the bracket. Still short enough that
     *  the sentence-end/no-letter checks in extendStartBack() catch the
     *  common case of admin prose right before a quote either way. */
    private const LEAD_IN_TOLERANCE = 2;

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
            // Trailing whitespace left over from $between (e.g. the space
            // before "١٨ هود:" in the source) would otherwise double up
            // with formatCitation()'s own leading space.
            $out .= $matchedVerse ? rtrim($runOutput) : $runOutput;

            // Only re-render the citation if it genuinely followed a
            // detected verse — otherwise this "citation-shaped" text was
            // just an ordinary "كلمة أخرى:"-style phrase in the author's
            // own prose, and must be left exactly as written.
            $out .= $matchedVerse
                ? self::formatCitation($matches[1][$i][0], $matches[2][$i][0])
                : $citation;

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
     * into the quote that follows it).
     */
    private static function extendStartBack(int $tokenIndex, int $floor, array $tokens, array $tokenWordIndex, int $tolerance): int
    {
        $targetWordIndex = max($tokenWordIndex[$tokenIndex] - $tolerance, $tokenWordIndex[$floor] ?? 0);

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

    private static function wrap(string $innerText, string $accent): string
    {
        return '<span class="quran-verse quran-verse--'.$accent.'">'.self::OPEN_BRACKET.$innerText.self::CLOSE_BRACKET.'</span>';
    }
}
