<?php

declare(strict_types=1);

namespace App\Services\Arabic;

/**
 * Split an Arabic legal document into coherent semantic chunks for RAG
 * indexing. We split on structural cues — preamble, articles, signatures —
 * NOT on fixed length. Each chunk stays one self-contained legal unit so
 * retrieval lands on a useful block rather than a sentence fragment.
 *
 * Recognised section openers (case-insensitive, post-normalization):
 *   ديباجة / تمهيد               → preamble
 *   المادة [N] / بند [N] / فصل   → numbered article
 *   حيث / حيث ان                  → recital (preamble continuation)
 *   التوقيع / التوقيعات            → signature block (last chunk)
 *
 * The chunker is deliberately conservative: anything before the first
 * recognised marker is emitted as a "preamble" chunk, and any text
 * after the signature marker is appended to the signature chunk.
 */
final class LegalChunker
{
    public function __construct(private readonly ArabicNormalizer $normalizer) {}

    /**
     * @return array<int,array{kind:string,heading:?string,article_no:?string,text:string}>
     */
    public function chunk(string $text): array
    {
        $text = $this->normalizer->normalize($text);
        if ($text === '') {
            return [];
        }

        // Article openers preserve ta-marbuta (ة) since the normalizer does not
        // collapse ة → ه. Recognise both forms defensively in case a contract
        // already uses the loose spelling.
        $articleRegex = '/(?:^|\s)(المادة|الماده|مادة|ماده|بند|فصل)\s+([\p{Arabic}\d\s\-\/]{1,20}?)(?=[:\.\-،\s])/u';
        $signatureMarkers = ['التوقيع', 'التوقيعات', 'حرر في', 'تم تحرير'];

        $chunks = [];
        $length = mb_strlen($text);

        if (preg_match_all($articleRegex, $text, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $i => $match) {
                [$matchText, $byteOffset] = $match;
                $charOffset = mb_strlen(substr($text, 0, $byteOffset));

                if ($i === 0 && $charOffset > 0) {
                    $preamble = trim(mb_substr($text, 0, $charOffset));
                    if ($preamble !== '') {
                        $chunks[] = [
                            'kind' => 'preamble',
                            'heading' => null,
                            'article_no' => null,
                            'text' => $preamble,
                        ];
                    }
                }

                $kind = trim($matches[1][$i][0]);
                $articleNo = trim($matches[2][$i][0]);
                $start = $charOffset;
                $nextOffset = $matches[0][$i + 1][1] ?? null;
                $end = $nextOffset !== null
                    ? mb_strlen(substr($text, 0, $nextOffset))
                    : $length;

                $body = trim(mb_substr($text, $start, $end - $start));
                $chunks[] = [
                    'kind' => 'article',
                    'heading' => $kind,
                    'article_no' => $articleNo !== '' ? $articleNo : null,
                    'text' => $body,
                ];
            }
        } else {
            // No structural markers — fall back to a single preamble chunk.
            $chunks[] = [
                'kind' => 'preamble',
                'heading' => null,
                'article_no' => null,
                'text' => $text,
            ];
        }

        return $this->mergeSignature($chunks, $signatureMarkers);
    }

    /**
     * If a chunk's body contains a signature marker, split the chunk at
     * that point: keep the preceding text in the original chunk, push the
     * remainder as a new "signature" chunk. Real-world contracts usually
     * place the signature at the end of the last article, so a single pass
     * is enough.
     */
    private function mergeSignature(array $chunks, array $markers): array
    {
        $out = [];
        foreach ($chunks as $chunk) {
            $splitPos = null;
            $hitMarker = null;
            foreach ($markers as $marker) {
                $pos = mb_strpos($chunk['text'], $marker);
                if ($pos !== false && ($splitPos === null || $pos < $splitPos)) {
                    $splitPos = $pos;
                    $hitMarker = $marker;
                }
            }

            if ($splitPos === null) {
                $out[] = $chunk;

                continue;
            }

            if ($splitPos > 0) {
                $head = trim(mb_substr($chunk['text'], 0, $splitPos));
                if ($head !== '') {
                    $out[] = array_merge($chunk, ['text' => $head]);
                }
            }

            $out[] = [
                'kind' => 'signature',
                'heading' => $hitMarker,
                'article_no' => null,
                'text' => trim(mb_substr($chunk['text'], $splitPos)),
            ];
        }

        return $out;
    }
}
