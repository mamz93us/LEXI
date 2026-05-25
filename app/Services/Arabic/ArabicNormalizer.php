<?php

declare(strict_types=1);

namespace App\Services\Arabic;

/**
 * Canonical Arabic text normalization used identically on RAG ingestion
 * AND retrieval queries. Drift between the two sides silently destroys
 * recall, so every call site must funnel through this class.
 *
 * Decisions (documented for review):
 *  - alef variants أ إ آ ٱ → ا  (improves recall, near-zero false positives)
 *  - alef-maqsura ى → ي  (Egyptian-academic convention; matches typed text)
 *  - tashkeel (all short vowels / sukun / shadda) is stripped
 *  - tatweel ـ removed
 *  - whitespace collapsed to single spaces, leading/trailing trimmed
 *  - **ta-marbuta ة is NOT converted to ه** — it carries semantic weight
 *    (feminine, plurals, possessive constructs). Converting it would mangle
 *    contract terminology. Re-evaluate if retrieval recall is poor.
 *  - latin punctuation kept as-is (caller can lower-case before passing in)
 */
final class ArabicNormalizer
{
    private const ALEF_VARIANTS = ['أ', 'إ', 'آ', 'ٱ', 'ٲ', 'ٳ'];

    private const TASHKEEL = [
        "\u{064B}", // fathatan
        "\u{064C}", // dammatan
        "\u{064D}", // kasratan
        "\u{064E}", // fatha
        "\u{064F}", // damma
        "\u{0650}", // kasra
        "\u{0651}", // shadda
        "\u{0652}", // sukun
        "\u{0653}", // maddah above
        "\u{0654}", // hamza above
        "\u{0655}", // hamza below
        "\u{0656}", // subscript alef
        "\u{0670}", // superscript alef
    ];

    private const TATWEEL = "\u{0640}";

    public function normalize(string $text): string
    {
        $out = str_replace(self::ALEF_VARIANTS, 'ا', $text);
        $out = str_replace('ى', 'ي', $out);
        $out = str_replace(self::TASHKEEL, '', $out);
        $out = str_replace(self::TATWEEL, '', $out);

        // Collapse any unicode whitespace (incl. tabs, line breaks, NBSP) to single spaces.
        $out = preg_replace('/\s+/u', ' ', $out);

        return trim($out ?? '');
    }

    /**
     * Useful for debugging which transformations fired.
     *
     * @return array<string,string>
     */
    public function diff(string $input): array
    {
        return [
            'input' => $input,
            'normalized' => $this->normalize($input),
        ];
    }
}
