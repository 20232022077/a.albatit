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

    public function test_a_two_word_lead_in_reaches_the_verses_own_unmarked_opening_word(): void
    {
        // "إِنِّي" (plain kasra/shadda only) is the verse's own first word,
        // but only the word after it ("إِلَىٰ", via the dagger-alif) carries
        // any marker -- a 1-word lead-in reach would strand "إِنِّي" outside
        // the bracket even though it's grammatically part of the verse, not
        // the author's own lead-in prose.
        $out = $this->render('وصار يستقبله بثقة: إِنِّي ذَاهِبٌ إِلَىٰ رَبِّي سَيَهۡدِينِ ٩٩ الصَّافَّات :.');

        $this->assertStringContainsString('﴿إِنِّي ذَاهِبٌ إِلَىٰ رَبِّي سَيَهۡدِينِ﴾', $out);
        $this->assertStringNotContainsString('﴿بثقة', $out);
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
}
