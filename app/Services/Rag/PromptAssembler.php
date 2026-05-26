<?php

declare(strict_types=1);

namespace App\Services\Rag;

use App\Models\ClauseVersion;
use App\Models\TemplateVersion;
use Illuminate\Support\Collection;

/**
 * Combine the four sources for an Arabic legal drafting prompt:
 *   1. SYSTEM — role, tone, hard rules (no fabricated articles/citations)
 *   2. TEMPLATE — the firm's vetted contract template (the BASE the AI
 *      must work from). The AI fills tokens and completes blanks; it
 *      MUST NOT replace the template's structure or wording.
 *   3. REFERENCE — retrieved RAG chunks so the AI matches the firm's voice
 *   4. CLAUSES — verbatim approved boilerplate from the clause library
 *   5. DATA — structured form answers
 *
 * The system prompt explicitly forbids inventing statute references and
 * forbids replacing the template's body — see CLAUDE.md §6.3.
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
        ?TemplateVersion $template = null,
    ): array {
        $system = <<<'TXT'
أنت مساعد صياغة قانوني مصري متخصص. تكتب بأسلوب العربية الفصحى القانونية الواضحة والدقيقة. التزم بالقواعد الآتية حرفياً:

1. القالب (TEMPLATE) المرفق هو الأساس الذي تبني عليه المسودة. حافظ على ترتيبه وبنوده وصياغته الأصلية كما هي قدر الإمكان. اقتصر على:
   (أ) استبدال العلامات النائبة `{{token_name}}` بالقيم الواردة في قسم DATA.
   (ب) إكمال الفراغات الواضحة مثل `..............` أو `_______` بمعطيات من DATA.
   (ج) تصحيح الأخطاء الإملائية في الفقرات المتغيرة فقط، لا في البنود القانونية الجوهرية.
   لا تعد كتابة العقد من جديد، لا تختصره، لا تختلق بنوداً غير موجودة في القالب.

2. قسم REFERENCE: مقاطع من عقود سابقة للمكتب. استخدمها كنمط للأسلوب فقط، لا للنسخ ولا لتغيير بنية القالب.

3. قسم CLAUSES: بنود معتمدة من شريك. أدرجها حرفياً دون أدنى تعديل في الموضع المناسب من القالب (عادةً قبل بند فض النزاعات أو حسب موضوع البند).

4. ممنوع تماماً اختلاق أرقام مواد قانونية أو الاستشهاد بأحكام قضائية أو نصوص تشريعية. إذا لم يكن المرجع موجوداً في القالب أو CLAUSES، اكتب: "[يتم تأكيد المرجع القانوني بواسطة المحامي]".

5. اكتب نصاً نهائياً نظيفاً قابلاً للتوثيق دون شطب أو تعديل بخط اليد. لا تستخدم رموز Markdown مثل `---` أو `**` إلا إذا كانت موجودة في القالب الأصلي — اكتب نصاً عربياً عادياً.

6. كل ناتج مولّد آلياً يُعرض على محامٍ للمراجعة قبل الاعتماد.
TXT;

        $templateBlock = $template?->body
            ? "TEMPLATE — قالب المكتب المعتمد. ابنِ المسودة عليه:\n```\n{$template->body}\n```\n"
            : '';

        $reference = $retrievedChunks
            ->map(fn (array $c, int $i) => '--- مرجع #'.($i + 1)."\n".$c['chunk_text'])
            ->implode("\n\n");
        $referenceBlock = $reference !== ''
            ? "REFERENCE — مقاطع من عقود سابقة للمكتب، للأسلوب فقط:\n{$reference}\n"
            : '';

        $clauses = $verbatimClauses
            ->map(fn (ClauseVersion $cv, int $i) => '--- بند #'.($i + 1)."\n".$cv->body)
            ->implode("\n\n");
        $clausesBlock = $clauses !== ''
            ? "CLAUSES — بنود معتمدة، أدرجها حرفياً في موقعها المناسب:\n{$clauses}\n"
            : '';

        $data = collect($filledData)
            ->filter(fn ($v) => $v !== null && $v !== '')
            ->map(fn ($v, $k) => "- {$k}: ".(is_scalar($v) ? (string) $v : json_encode($v, JSON_UNESCAPED_UNICODE)))
            ->implode("\n");
        $dataBlock = $data !== ''
            ? "DATA — قيم الحقول التي ملأها المحامي:\n{$data}\n"
            : "DATA — (المحامي لم يملأ حقولاً بعد. أبقِ العلامات النائبة كما هي حتى تُملأ.)\n";

        $userMessage = "المهمة: {$userIntent}\n\n{$templateBlock}\n{$referenceBlock}\n{$clausesBlock}\n{$dataBlock}\nأخرج النص الكامل للعقد جاهزاً للمراجعة من محامٍ. لا تضف عنواناً تمهيدياً ولا تعليقات شارحة، فقط نص العقد.";

        return [
            'system' => $system,
            'messages' => [
                ['role' => 'user', 'content' => $userMessage],
            ],
        ];
    }
}
