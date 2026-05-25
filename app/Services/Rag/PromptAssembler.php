<?php

declare(strict_types=1);

namespace App\Services\Rag;

use App\Models\ClauseVersion;
use Illuminate\Support\Collection;

/**
 * Combine the three sources for an Arabic legal drafting prompt:
 *   1. SYSTEM — role, tone, hard rules (no fabricated articles/citations)
 *   2. REFERENCE — retrieved RAG chunks so the AI imitates the firm's style
 *   3. CLAUSES — verbatim approved boilerplate from the clause library
 *   4. DATA — structured form answers
 *
 * The system prompt explicitly forbids inventing statute references —
 * see CLAUDE.md §6.3.
 */
final class PromptAssembler
{
    /**
     * @param  Collection<int, array>  $retrievedChunks
     * @param  Collection<int, ClauseVersion>  $verbatimClauses
     * @param  array<string, mixed>  $filledData
     * @return array{system:string, messages:array<int, array{role:string,content:string}>}
     */
    public function assemble(
        string $userIntent,
        Collection $retrievedChunks,
        Collection $verbatimClauses,
        array $filledData,
    ): array {
        $system = <<<'TXT'
أنت مساعد صياغة قانوني مصري متخصص. تكتب بأسلوب العربية الفصحى القانونية، بنفس نبرة وبنية المقاطع المرجعية المرفقة. التزم بالقواعد التالية بدقة:

1. استخدم المقاطع المرفقة في قسم "REFERENCE" كنمط للأسلوب والصياغة، لا للنسخ الحرفي.
2. أدرج المقاطع المرفقة في قسم "CLAUSES" كما هي حرفياً دون تعديل.
3. ممنوع تماماً اختلاق أرقام مواد قانونية أو الاستشهاد بأحكام قضائية أو نصوص تشريعية. إذا لم تكن متأكداً، اكتب مكان النص: "[يتم تأكيد المرجع القانوني بواسطة المحامي]".
4. الناتج يجب أن يكون نصاً نهائياً نظيفاً قابلاً للتوثيق دون شطب أو تعديل بخط اليد.
5. كل ناتج مولّد آلياً يُعرض على محامٍ للمراجعة قبل الاعتماد.
TXT;

        $reference = $retrievedChunks
            ->map(fn (array $c, int $i) => '--- مرجع #'.($i + 1)."\n".$c['chunk_text'])
            ->implode("\n\n");

        $clauses = $verbatimClauses
            ->map(fn (ClauseVersion $cv, int $i) => '--- بند #'.($i + 1)."\n".$cv->body)
            ->implode("\n\n");

        $data = collect($filledData)
            ->map(fn ($v, $k) => "- {$k}: ".(is_scalar($v) ? (string) $v : json_encode($v, JSON_UNESCAPED_UNICODE)))
            ->implode("\n");

        $userMessage = <<<TXT
المهمة: {$userIntent}

REFERENCE — مقاطع من عقود سابقة للمكتب نفسه، استخدمها كنمط للأسلوب:
{$reference}

CLAUSES — بنود معتمدة، أدرجها حرفياً:
{$clauses}

DATA — بيانات النموذج المعبأ:
{$data}

اكتب المسودة الكاملة بالعربية الفصحى، جاهزة للمراجعة من محامٍ.
TXT;

        return [
            'system' => $system,
            'messages' => [
                ['role' => 'user', 'content' => $userMessage],
            ],
        ];
    }
}
