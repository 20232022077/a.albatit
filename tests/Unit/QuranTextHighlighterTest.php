<?php

namespace Tests\Unit;

use App\Support\QuranTextHighlighter;
use PHPUnit\Framework\TestCase;

class QuranTextHighlighterTest extends TestCase
{
    private function render(string $text, string $accent = 'slate'): string
    {
        return QuranTextHighlighter::highlight(nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8')), $accent);
    }

    public function test_a_marked_verse_is_wrapped_in_quranic_brackets(): void
    {
        $out = $this->render('إِنَّ فِرۡعَوۡنَ عَلَا فِي ٱلۡأَرۡضِ');

        $this->assertStringContainsString('﴿إِنَّ فِرۡعَوۡنَ عَلَا فِي ٱلۡأَرۡضِ﴾', $out);
    }

    public function test_ordinary_prose_with_a_colon_is_never_wrapped(): void
    {
        $out = $this->render('القرآن لم يهادن ظلمًا، ولم يُجمّل استبدادًا، بل أعلن: هكذا يبدأ الوصف طاغية يتعالى ويفرّق ويستعبد.');

        $this->assertStringNotContainsString('quran-verse', $out);
    }

    public function test_citation_is_reformatted_to_surah_then_number_regardless_of_source_order(): void
    {
        $out = $this->render('إِنَّ فِرۡعَوۡنَ عَلَا فِي ٱلۡأَرۡضِ وَجَعَلَ أَهۡلَهَا شِيَعٗا ٤ القَصَص :');

        $this->assertStringContainsString('(القَصَص: ٤)', $out);
    }

    public function test_a_narrative_word_before_a_colon_is_never_treated_as_a_fake_citation(): void
    {
        // Real production regression: "قال:" (an ordinary "he said:" right
        // after a properly-wrapped verse and before an unrelated quote)
        // matches CITATION_PATTERN's generic "single word + colon" shape,
        // but isn't a real surah name -- it must stay exactly as written,
        // not turn into a "(قال)" badge, and critically must not silently
        // consume the colon a later verse on the same line needs for its
        // own lead-in search (see findPrecedingColonWordIndex()).
        $out = $this->render('فسمعه يقرأ هذه الآية : ٱتَّخَذُوٓاْ أَحۡبَارَهُمۡ أَرۡبَابٗا ٣١ التَّوۡبَة : قال: فقلت له بثقة: إِنِّي ذَاهِبٌ إِلَىٰ رَبِّي سَيَهۡدِينِ ٩٩ الصَّافَّات :.');

        $this->assertStringNotContainsString('(قال)', $out);
        $this->assertStringContainsString('قال:', $out);
        $this->assertStringContainsString('﴿ٱتَّخَذُوٓاْ أَحۡبَارَهُمۡ أَرۡبَابٗا﴾', $out);
        $this->assertStringContainsString('﴿إِنِّي ذَاهِبٌ إِلَىٰ رَبِّي سَيَهۡدِينِ﴾', $out);
    }

    public function test_a_verse_number_glued_to_the_surah_name_still_splits_correctly(): void
    {
        $out = $this->render('أَلَا لَعۡنَةُ ٱللَّهِ عَلَى ٱلظَّٰلِمِينَ ١٨هُود : .');

        $this->assertStringContainsString('(هُود: ١٨)', $out);
        $this->assertStringNotContainsString('١٨هُود', $out);
    }

    public function test_a_comma_between_two_citations_never_leaks_inside_the_second_bracket(): void
    {
        $out = $this->render('أَلَا لَعۡنَةُ ٱللَّهِ عَلَى ٱلظَّٰلِمِينَ ١٨ هُود : ، وَلَا تَرۡكَنُوٓاْ إِلَى ٱلَّذِينَ ظَلَمُواْ فَتَمَسَّكُمُ ٱلنَّارُ ١١٣ هُود : .');

        $this->assertStringNotContainsString('﴿،', $out);
        $this->assertStringContainsString('﴿وَلَا تَرۡكَنُوٓاْ', $out);
    }

    public function test_a_grammatical_lead_in_word_is_folded_into_the_verse(): void
    {
        $out = $this->render('إِنَّ أَكۡرَمَكُمۡ عِندَ ٱللَّهِ أَتۡقَىٰكُمۡۚ');

        $this->assertStringContainsString('﴿إِنَّ أَكۡرَمَكُمۡ', $out);
    }

    public function test_a_lead_in_word_is_not_pulled_across_a_sentence_boundary(): void
    {
        $out = $this->render('يرفض الخرافة، ويتحرر من عبادة الأسلاف.  بَلۡ نَتَّبِعُ مَآ أَلۡفَيۡنَا عَلَيۡهِ ءَابَآءَنَآۚ البَقَرَةِ :');

        $this->assertStringNotContainsString('﴿الأسلاف', $out);
        $this->assertStringContainsString('﴿بَلۡ نَتَّبِعُ', $out);
    }

    public function test_a_colon_before_an_unmarked_verse_opening_reaches_all_the_way_back_to_it(): void
    {
        // "إِنِّي" (plain kasra/shadda only) is the verse's own first word,
        // but only the word after it ("إِلَىٰ", via the dagger-alif) carries
        // any marker -- a 1-word lead-in reach would strand "إِنِّي" outside
        // the bracket even though it's grammatically part of the verse, not
        // the author's own lead-in prose. The colon right before it is what
        // makes reaching back two words safe here (see
        // findPrecedingColonWordIndex()).
        $out = $this->render('وصار يستقبله بثقة: إِنِّي ذَاهِبٌ إِلَىٰ رَبِّي سَيَهۡدِينِ ٩٩ الصَّافَّات :.');

        $this->assertStringContainsString('﴿إِنِّي ذَاهِبٌ إِلَىٰ رَبِّي سَيَهۡدِينِ﴾', $out);
        $this->assertStringNotContainsString('﴿بثقة', $out);
    }

    public function test_a_quoted_aside_before_the_verse_is_never_pulled_into_the_bracket(): void
    {
        // Real production regression: "من "يا لَيْتَ" إلى قُمْ (المدثر:)" --
        // "قُمْ" alone is the verse (Al-Muddathir 74:2); "من "يا لَيْتَ" إلى"
        // is the author's own rhetorical contrast, with no colon anywhere
        // to justify reaching back across the quoted "يا لَيْتَ" at all.
        $out = $this->render('القرآن نقل الأمة من التواكل إلى الإرادة، من "يا لَيْتَ" إلى قُمۡ المُدَّثِّر :.');

        $this->assertStringNotContainsString('﴿يا', $out);
        $this->assertStringNotContainsString('﴿لَيْتَ', $out);
        $this->assertStringContainsString('لَيْتَ', $out);
        $this->assertStringContainsString('قُمۡ﴾', $out);
    }

    public function test_a_colon_wins_over_a_wide_anchor_lead_in_reach_instead_of_just_widening_it(): void
    {
        // Real production regression: a terminal waqf mark on the verse's
        // *last* word (ۗ) makes the whole marked-word group anchor-tolerant
        // (see ANCHOR_MAX_GAP in runHighlight()), and that wide tolerance
        // alone would happily reach right past the colon into the author's
        // own lead-in sentence ("لكن القرآن أعلن أول ما أعلن:") -- a colon
        // found on the way back must override that wide tolerance outright,
        // not just be blended into it via a plain minimum.
        $out = $this->render('لكن القرآن أعلن أول ما أعلن: إِنَّ ٱللَّهَ لَا يُغَيِّرُ مَا بِقَوۡمٍ حَتَّىٰ يُغَيِّرُواْ مَا بِأَنفُسِهِمۡۗ الرَّعۡد :.');

        $this->assertStringContainsString('﴿إِنَّ ٱللَّهَ لَا يُغَيِّرُ مَا بِقَوۡمٍ حَتَّىٰ يُغَيِّرُواْ مَا بِأَنفُسِهِمۡۗ﴾', $out);
        $this->assertStringNotContainsString('﴿لكن', $out);
    }

    public function test_a_verse_with_only_one_marked_word_is_still_wrapped_when_a_citation_follows(): void
    {
        // "فَبِأَيِّ ءَالَآءِ رَبِّكُمَا تُكَذِّبَانِ" (Ar-Rahman's repeated refrain)
        // has no sukun letter or wasla-alif anywhere in it -- in any
        // correctly-typed Uthmani rendering it carries exactly one marker
        // (the "آ" in "ءَالَآءِ", stored decomposed -- alef U+0627 + combining
        // madda U+0653, per real production content; built from the same
        // codepoints here rather than hand-typed, since typing "آ" normally
        // silently produces the precomposed U+0622 instead, which carries no
        // marker at all and would defeat the point of this test), never a
        // second marker, so it can never clear MIN_MARKED_WORDS on marker
        // density alone. A genuine trailing citation is independently
        // strong enough evidence.
        $aaDecomposed = "\u{0627}\u{0653}";
        $verse = "فَبِأَيِّ ءَالَ{$aaDecomposed}ءِ رَبِّكُمَا تُكَذِّبَانِ";
        $out = $this->render("{$verse} الرَّحۡمَٰن :");

        $this->assertStringContainsString("﴿{$verse}﴾", $out);
        $this->assertStringContainsString('(الرَّحۡمَٰن)', $out);
    }

    public function test_a_single_word_verse_alone_on_its_own_line_is_still_wrapped(): void
    {
        $out = $this->render('ٱقۡرَأۡ');

        $this->assertStringContainsString('﴿ٱقۡرَأۡ﴾', $out);
    }

    public function test_a_long_verse_in_plain_script_with_only_waqf_marks_is_wrapped_whole(): void
    {
        $out = $this->render('۞ لَيْسَ الْبِرَّ أَنْ تُوَلُّوا وُجُوهَكُمْ قِبَلَ الْمَشْرِقِ وَالْمَغْرِبِ وَلَٰكِنَّ الْبِرَّ مَنْ آمَنَ بِاللَّهِ وَالْيَوْمِ الْآخِرِ وَالْمَلَائِكَةِ وَالْكِتَابِ وَالنَّبِيِّينَ وَآتَى الْمَالَ عَلَىٰ حُبِّهِ ذَوِي الْقُرْبَىٰ وَالْيَتَامَىٰ وَالْمَسَاكِينَ وَابْنَ السَّبِيلِ وَالسَّائِلِينَ وَفِي الرِّقَابِ وَأَقَامَ الصَّلَاةَ وَآتَى الزَّكَاةَ وَالْمُوفُونَ بِعَهْدِهِمْ إِذَا عَاهَدُوا ۖ وَالصَّابِرِينَ فِي الْبَأْسَاءِ وَالضَّرَّاءِ وَحِينَ الْبَأْسِ ۗ أُولَٰئِكَ الَّذِينَ صَدَقُوا ۖ وَأُولَٰئِكَ هُمُ الْمُتَّقُونَ ۝ 177');

        $this->assertStringContainsString('وَأُولَٰئِكَ هُمُ الْمُتَّقُونَ ۝﴾', $out);
        $this->assertStringNotContainsString('177﴾', $out);
    }

    public function test_a_two_word_surah_name_written_precomposed_is_matched(): void
    {
        $out = $this->render('وَهُمۡ يَعۡلَمُونَ ٧٨ آل عِمۡرَان :');

        $this->assertStringContainsString('(آل عِمۡرَان: ٧٨)', $out);
    }

    public function test_a_two_word_surah_name_written_with_a_decomposed_alef_madda_is_matched(): void
    {
        // "آل" written as alef (U+0627) + combining madda above (U+0653) + lam,
        // instead of the single precomposed "آ" (U+0622) — real admin-typed
        // content sometimes stores it this way, and only a byte-identical
        // literal match would otherwise miss it. The expectation below is
        // built from the same $decomposed variable rather than retyped, since
        // a hand-typed "آل" here would silently use the precomposed form and
        // never byte-match the decomposed one even though both render
        // identically.
        $decomposed = "\u{0627}\u{0653}\u{0644}";
        $out = $this->render("وَهُمۡ يَعۡلَمُونَ ٧٨ {$decomposed} عِمۡرَان :");

        $this->assertStringContainsString("({$decomposed} عِمۡرَان: ٧٨)", $out);
    }

    public function test_a_two_word_surah_name_with_a_dropped_base_alef_is_still_matched(): void
    {
        // Real production content: "آل" typed with the base alef missing
        // entirely, leaving only an orphaned combining madda (U+0653)
        // floating right before "ل" — e.g. a copy/paste that dropped the
        // first character. The citation boundary must still land in the
        // right place instead of leaking the verse number and a fragment
        // of "آل" inside the verse bracket.
        $orphanedMadda = "\u{0653}\u{0644}";
        $out = $this->render("وَهُمۡ يَعۡلَمُونَ ٦٤ {$orphanedMadda} عِمۡرَان :");

        $this->assertStringContainsString('﴿وَهُمۡ يَعۡلَمُونَ﴾', $out);
        $this->assertStringContainsString("({$orphanedMadda} عِمۡرَان: ٦٤)", $out);
    }

    public function test_the_definite_article_prefix_is_not_mistaken_for_a_decomposed_aal(): void
    {
        $out = $this->render('إِنَّ فِرۡعَوۡنَ عَلَا فِي ٱلۡأَرۡضِ ٤ القَصَص :');

        $this->assertStringContainsString('(القَصَص: ٤)', $out);
    }

    public function test_highlight_excerpt_truncates_and_still_wraps_a_verse(): void
    {
        $out = QuranTextHighlighter::highlightExcerpt('إِنَّ فِرۡعَوۡنَ عَلَا فِي ٱلۡأَرۡضِ وَجَعَلَ أَهۡلَهَا شِيَعٗا يَسۡتَضۡعِفُ طَآئِفَةٗ مِّنۡهُمۡ', 30, 'slate');

        $this->assertStringContainsString('quran-verse', $out);
        $this->assertStringContainsString('...', $out);
    }

    public function test_highlight_excerpt_backfills_a_plain_tashkeel_epigraph_confirmed_later_in_the_full_body(): void
    {
        // The card preview only ever displays the first ~130-140 characters
        // of the body (well short of where "ذَٰلِكَ لِمَنۡ خَافَ مَقَامِي وَخَافَ وَعِيدِ
        // ١٤إِبۡرَاهِيم :" confirms the wording later on) — the confirmation has
        // to come from scanning the full raw body, not just the truncated,
        // displayed excerpt.
        $body = 'لِمَنْ خَافَ مَقَامِي وَخَافَ وَعِيدِ'
            ."\n\n".'كانت الجاهلية تفخر بالرجل إذا كان شرِهًا في شهوته، مسرفًا في أكله، مفرطًا في لهوه.'
            ."\n\n".'ذَٰلِكَ لِمَنۡ خَافَ مَقَامِي وَخَافَ وَعِيدِ ١٤إِبۡرَاهِيم :';

        $out = QuranTextHighlighter::highlightExcerpt($body, 90, 'slate');

        $this->assertStringContainsString('﴿لِمَنْ خَافَ مَقَامِي وَخَافَ وَعِيدِ﴾', $out);
    }

    public function test_highlight_excerpt_normalizes_crlf_without_corrupting_arabic_text(): void
    {
        $out = QuranTextHighlighter::highlightExcerpt("سطر أول\r\n\r\nسطر ثانٍ", 100, 'slate');

        $this->assertStringNotContainsString("\u{FFFD}", $out);
    }

    public function test_highlight_paragraphs_backfills_a_plain_tashkeel_epigraph_from_a_later_paragraph(): void
    {
        // "لِمَنْ خَافَ مَقَامِي وَخَافَ وَعِيدِ" (Ibrahim 14:14) here uses only
        // ordinary fatha/kasra/sukun — nothing in MARKER_PATTERN — so on its
        // own, as a standalone opening line, there is no Unicode signal at
        // all that it's a verse. It only gets wrapped because the same
        // wording is confirmed elsewhere in the body, in Uthmani script with
        // a citation.
        $paragraphs = [
            nl2br(htmlspecialchars('لِمَنْ خَافَ مَقَامِي وَخَافَ وَعِيدِ', ENT_QUOTES, 'UTF-8')),
            nl2br(htmlspecialchars('نص عادي بينهما.', ENT_QUOTES, 'UTF-8')),
            nl2br(htmlspecialchars('ذَٰلِكَ لِمَنۡ خَافَ مَقَامِي وَخَافَ وَعِيدِ ١٤إِبۡرَاهِيم :', ENT_QUOTES, 'UTF-8')),
        ];

        $result = QuranTextHighlighter::highlightParagraphs($paragraphs, 'slate');

        $this->assertCount(3, $result);
        $this->assertStringContainsString('﴿لِمَنْ خَافَ مَقَامِي وَخَافَ وَعِيدِ﴾', $result[0]);
        $this->assertStringNotContainsString('quran-verse', $result[1]);
        $this->assertStringContainsString('(إِبۡرَاهِيم: ١٤)', $result[2]);
    }

    public function test_highlight_paragraphs_does_not_backfill_an_unrelated_short_line(): void
    {
        $paragraphs = [
            nl2br(htmlspecialchars('هذا سطر عادي قصير جدًا.', ENT_QUOTES, 'UTF-8')),
            nl2br(htmlspecialchars('إِنَّ فِرۡعَوۡنَ عَلَا فِي ٱلۡأَرۡضِ', ENT_QUOTES, 'UTF-8')),
        ];

        $result = QuranTextHighlighter::highlightParagraphs($paragraphs, 'slate');

        $this->assertStringNotContainsString('quran-verse', $result[0]);
    }

    public function test_an_explicitly_marked_verse_is_wrapped_verbatim_with_no_marker_needed(): void
    {
        // Ordinary typed tashkeel, precomposed forms throughout -- none of
        // MARKER_PATTERN's Uthmani-only marks anywhere. The heuristic path
        // would never wrap this on its own; an explicit [[...]] marker must
        // still wrap it, since that's the entire point of the escape hatch.
        $out = $this->render('نص عادي قبلها. [[هَذَا نَصٌّ عَادِيٌّ تَمَامًا بِلَا أَيِّ عَلَامَاتٍ مُمَيَّزَةٍ]] بعدها نص عادي.');

        $this->assertStringContainsString('﴿هَذَا نَصٌّ عَادِيٌّ تَمَامًا بِلَا أَيِّ عَلَامَاتٍ مُمَيَّزَةٍ﴾', $out);
        $this->assertStringContainsString('نص عادي قبلها.', $out);
        $this->assertStringContainsString('بعدها نص عادي.', $out);
    }

    public function test_a_citation_right_after_an_explicit_marker_is_reformatted(): void
    {
        $out = $this->render('[[نَصٌّ مُعَلَّمٌ يَدَوِيًّا]] ٤ القصص :');

        $this->assertStringContainsString('﴿نَصٌّ مُعَلَّمٌ يَدَوِيًّا﴾', $out);
        $this->assertStringContainsString('(القصص: ٤)', $out);
    }

    public function test_an_explicit_marker_with_no_trailing_citation_is_wrapped_alone(): void
    {
        $out = $this->render('[[نَصٌّ بِلَا اسْتِشْهَادٍ بَعْدَهُ]] وبقية الجملة عادية.');

        $this->assertStringContainsString('﴿نَصٌّ بِلَا اسْتِشْهَادٍ بَعْدَهُ﴾', $out);
        $this->assertStringNotContainsString('quran-citation', $out);
    }

    public function test_two_explicit_markers_on_the_same_line_do_not_bleed_into_each_other(): void
    {
        $out = $this->render('[[أَوَّلُ آيَةٍ]] وبينهما نص. [[ثَانِي آيَةٍ]]');

        $this->assertStringContainsString('﴿أَوَّلُ آيَةٍ﴾', $out);
        $this->assertStringContainsString('﴿ثَانِي آيَةٍ﴾', $out);
        $this->assertStringContainsString('وبينهما نص.', $out);
    }

    public function test_strip_font_artifacts_removes_words_fonts_fake_bracket_ligatures(): void
    {
        // The exact three characters a real admin reported seeing after
        // pasting a verse "bracketed" using Word's KFGQPC HAFS Uthmanic
        // Script font: U+FD51/FD53/FD54, standard Unicode ligature
        // characters for unrelated letter combinations (teh-hah-jeem,
        // teh-hah-meem, teh-khah-meem) that this specific font's glyph
        // table repurposes to *draw* like a bracket -- meaningless, garbled
        // text in any other font, this site's included.
        $fakeBracket = "\u{FD51}\u{FD53}\u{FD54}";

        $cleaned = QuranTextHighlighter::stripFontArtifacts("{$fakeBracket}إِنَّ فِرۡعَوۡنَ{$fakeBracket}");

        $this->assertSame('إِنَّ فِرۡعَوۡنَ', $cleaned);
    }

    public function test_strip_font_artifacts_keeps_the_sites_own_ornate_brackets(): void
    {
        $text = '﴿إِنَّ فِرۡعَوۡنَ﴾';

        $this->assertSame($text, QuranTextHighlighter::stripFontArtifacts($text));
    }

    public function test_strip_font_artifacts_keeps_legitimate_religious_ligatures(): void
    {
        // ﷺ (U+FDFA, "sallallahou alayhe wasallam") and ﷽ (U+FDFD,
        // "bismillah ar-rahman ar-raheem") are real, commonly and
        // intentionally typed ligatures on an Islamic site -- must survive
        // even though they sit in the same Unicode block as the garbled
        // font-artifact ligatures being stripped.
        $text = "محمد \u{FDFA} \u{FDFD}";

        $this->assertSame($text, QuranTextHighlighter::stripFontArtifacts($text));
    }

    public function test_a_verse_pasted_with_fake_font_bracket_ligatures_is_still_detected_and_wrapped_correctly(): void
    {
        $fakeBracket = "\u{FD51}\u{FD53}\u{FD54}";
        $raw = "قبل الآية.\n{$fakeBracket}إِنَّ فِرۡعَوۡنَ عَلَا فِي ٱلۡأَرۡضِ{$fakeBracket} ٤ القصص :";

        $out = QuranTextHighlighter::highlightExcerpt($raw, 200, 'slate');

        $this->assertStringContainsString('﴿إِنَّ فِرۡعَوۡنَ عَلَا فِي ٱلۡأَرۡضِ﴾', $out);
        $this->assertStringContainsString('(القصص: ٤)', $out);
        $this->assertStringNotContainsString("\u{FD51}", $out);
    }

    public function test_this_fonts_own_verse_bracket_ligatures_are_trusted_verbatim_even_with_no_markers(): void
    {
        // Real production regression: "فَبِأَيِّ ءَالَآءِ رَبِّكُمَا تُكَذِّبَانِ" (the
        // Ar-Rahman refrain) carries none of MARKER_PATTERN's marks at all,
        // so once the old code simply deleted the font's own U+FD5F/FD5E
        // bracket ligatures around it, the diacritic heuristic below found
        // nothing to detect and the verse rendered as plain, unformatted
        // text. Converting those ligatures to "[[...]]" instead (see
        // VERSE_OPEN_ARTIFACT) routes it through the no-guessing explicit
        // path, which needs no markers at all.
        $raw = "مقدمة.\n\u{FD5F}فَبِأَيِّ ءَالَآءِ رَبِّكُمَا تُكَذِّبَانِ\u{FD5E} ، تتكرر كثيرًا.";

        $out = QuranTextHighlighter::highlightExcerpt($raw, 200, 'slate');

        $this->assertStringContainsString('﴿فَبِأَيِّ ءَالَآءِ رَبِّكُمَا تُكَذِّبَانِ﴾', $out);
        $this->assertStringContainsString('، تتكرر كثيرًا', $out);
    }

    public function test_a_verse_opening_a_paragraph_via_font_bracket_ligatures_is_wrapped_not_left_plain(): void
    {
        // Real production regression: "وَقُلِ ٱعۡمَلُواْ" as a body's opening
        // line, ligature-bracketed in Word, rendered as bare unformatted
        // text on the live card excerpt.
        $raw = "\u{FD5F}وَقُلِ ٱعۡمَلُواْ\u{FD5E}\nجاء القرآن في زمن كان الناس فيه ينتظرون.";

        $out = QuranTextHighlighter::highlightExcerpt($raw, 60, 'slate');

        $this->assertStringContainsString('﴿وَقُلِ ٱعۡمَلُواْ﴾', $out);
    }

    public function test_a_quoted_idiom_before_a_font_bracketed_verse_stays_outside_it(): void
    {
        // Real production regression: with only the single word "قُمۡ"
        // ligature-bracketed in the source (correctly), the old
        // strip-then-reguess heuristic still reached back across the
        // authors own quoted "يا ليت" aside and swallowed it into the
        // verse. Trusting the font's own narrow bracket verbatim via the
        // explicit-marker path can't reach back at all.
        $raw = 'القرآن نقل الأمة من التواكل إلى الإرادة، من "يا ليت" إلى '
            ."\u{FD5F}قُمۡ\u{FD5E} \u{FD5D}المُدَّثِّر : \u{FD52}\u{FD5C}.";

        $out = QuranTextHighlighter::highlightExcerpt($raw, 200, 'slate');

        $this->assertStringContainsString('﴿قُمۡ﴾', $out);
        $this->assertStringNotContainsString('يا ليت﴾', $out);
        $this->assertStringNotContainsString('﴿يا ليت', $out);
        $this->assertStringContainsString('&quot;يا ليت&quot;', $out);
    }

    public function test_an_unrelated_sentence_around_a_narrow_font_bracketed_verse_is_never_swallowed(): void
    {
        // Real production regression: the source correctly ligature-
        // bracketed only "فَرِيضَةٗ مِّنَ ٱللَّهِۗ", but the old heuristic
        // over-extended the wrap to the entire surrounding sentence once
        // the bracket markers were simply deleted before it ran.
        $raw = 'ليست صدقة تطوعية، '."\u{FD5F}فَرِيضَةٗ مِّنَ ٱللَّهِۗ\u{FD5E}"
            .'، وجعلها مؤسسة كبيرة داخل المجتمع الإسلامي.';

        $out = QuranTextHighlighter::highlightExcerpt($raw, 200, 'slate');

        $this->assertStringContainsString('﴿فَرِيضَةٗ مِّنَ ٱللَّهِۗ﴾', $out);
        $this->assertStringContainsString('ليست صدقة تطوعية،', $out);
        $this->assertStringNotContainsString('تطوعية، ﴿', $out);
        $this->assertStringNotContainsString('الإسلامي.﴾', $out);
    }

    public function test_a_citation_glued_directly_onto_the_verse_close_ligature_still_splits_cleanly(): void
    {
        // Real production regression: the citation-open ligature was typed
        // with zero whitespace before it, directly onto the verse-close
        // ligature ("...بِٱلۡمَعۡرُوفِۚ" immediately followed by the citation
        // marker) — simply deleting both fused the surah name onto the
        // verse's own last word into one unsplittable token, dropping the
        // citation and pulling "البقرة" itself inside the bracket.
        $raw = 'فقالها القرآن صراحةً: '
            ."\u{FD5F}وَلَهُنَّ مِثۡلُ ٱلَّذِي عَلَيۡهِنَّ بِٱلۡمَعۡرُوفِۚ\u{FD5E}\u{FD5D}البَقَرَةِ : \u{FD58}\u{FD52}\u{FD52}\u{FD5C}";

        $out = QuranTextHighlighter::highlightExcerpt($raw, 200, 'slate');

        $this->assertStringContainsString('﴿وَلَهُنَّ مِثۡلُ ٱلَّذِي عَلَيۡهِنَّ بِٱلۡمَعۡرُوفِۚ﴾', $out);
        $this->assertStringContainsString('(البَقَرَةِ)', $out);
        $this->assertStringNotContainsString('البَقَرَةِ﴾', $out);
    }

    public function test_the_rare_uthmani_small_low_meem_mark_is_stripped_from_display(): void
    {
        // Real production regression: U+06ED (a genuine but rare Uthmani
        // annotation mark) has no dedicated glyph in this site's Qur'an
        // webfont and renders as a stray circular fallback glyph —
        // stripped by explicit editorial request.
        $out = QuranTextHighlighter::stripFontArtifacts("سَوَآءِ\u{06ED}");

        $this->assertSame('سَوَآءِ', $out);
    }

    public function test_an_unbalanced_font_bracket_ligature_falls_back_to_plain_deletion(): void
    {
        // A stray, unpaired ligature (e.g. an admin deleted just the
        // closing half while editing) must never leak a literal, visible
        // "[[" into the rendered prose.
        $out = QuranTextHighlighter::stripFontArtifacts("قبل \u{FD5F}إِنَّ فِرۡعَوۡنَ بعد");

        $this->assertStringNotContainsString('[[', $out);
        $this->assertStringNotContainsString("\u{FD5F}", $out);
    }
}
