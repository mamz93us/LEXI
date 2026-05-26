<?php

declare(strict_types=1);

namespace App\Services\Rag;

use App\Models\ClauseVersion;
use App\Models\TemplateVersion;
use App\Services\Documents\TokenReplacer;
use Illuminate\Support\Collection;

/**
 * Combine the four sources for an Arabic legal drafting prompt:
 *   1. SYSTEM — role, tone, hard rules (no fabricated articles/citations)
 *   2. TEMPLATE — the firm's vetted contract template (the BASE the AI
 *      must work from). Predefined-catalog tokens (`{{seller.name}}`,
 *      `{{contract.place}}`, etc.) are pre-substituted on our side so
 *      Claude never has to guess at party data — it sees real values.
 *      Only genuine "...." style blanks or true narrative gaps are left
 *      for the AI to complete.
 *   3. REFERENCE — retrieved RAG chunks so the AI matches the firm's voice
 *   4. CLAUSES — verbatim approved boilerplate from the clause library
 *      (also pre-substituted so embedded {{party}} tokens resolve)
 *   5. DATA — structured form answers (kept for transparency)
 *
 * The system prompt explicitly forbids inventing statute references and
 * forbids replacing the template's body — see CLAUDE.md §6.3.
 */
final class PromptAssembler
{
    public function __construct(private readonly TokenReplacer $tokens) {}

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
        $hasTemplate = (bool) ($template?->body ?? null);

        $systemWithTemplate = <<<'TXT'
أنت مساعد صياغة قانوني مصري متخصص. تكتب بأسلوب العربية الفصحى القانونية الواضحة والدقيقة. التزم بالقواعد الآتية حرفياً:

1. القالب (TEMPLATE) المرفق هو الأساس الذي تبني عليه المسودة. حافظ على ترتيبه وبنوده وصياغته الأصلية كما هي قدر الإمكان. اقتصر على:
   (أ) استبدال العلامات النائبة المتبقية `{{token_name}}` بالقيم الواردة في قسم DATA (معظمها تم استبداله مسبقاً، لكن قد تتبقى بعض العلامات).
   (ب) إكمال الفراغات الواضحة مثل `..............` أو `_______` بمعطيات من DATA.
   (ج) تصحيح الأخطاء الإملائية في الفقرات المتغيرة فقط، لا في البنود القانونية الجوهرية.
   لا تعد كتابة العقد من جديد، لا تختصره، لا تختلق بنوداً غير موجودة في القالب.

2. قسم REFERENCE: مقاطع من عقود سابقة للمكتب. استخدمها كنمط للأسلوب فقط، لا للنسخ ولا لتغيير بنية القالب.

3. قسم CLAUSES: بنود معتمدة من شريك. أدرجها حرفياً دون أدنى تعديل في الموضع المناسب من القالب (عادةً قبل بند فض النزاعات أو حسب موضوع البند).

4. ممنوع تماماً اختلاق أرقام مواد قانونية أو الاستشهاد بأحكام قضائية أو نصوص تشريعية. إذا لم يكن المرجع موجوداً في القالب أو CLAUSES، اكتب: "[يتم تأكيد المرجع القانوني بواسطة المحامي]".

5. اكتب نصاً نهائياً نظيفاً قابلاً للتوثيق دون شطب أو تعديل بخط اليد. لا تستخدم رموز Markdown مثل `---` أو `**` إلا إذا كانت موجودة في القالب الأصلي — اكتب نصاً عربياً عادياً.

6. كل ناتج مولّد آلياً يُعرض على محامٍ للمراجعة قبل الاعتماد.
TXT;

        $systemFromScratch = <<<'TXT'
أنت مساعد صياغة قانوني مصري متخصص. تكتب بأسلوب العربية الفصحى القانونية الواضحة والدقيقة، متبعاً الممارسة المعتمدة في المحاكم والشهر العقاري في مصر. التزم بالقواعد الآتية حرفياً:

1. لا يوجد قالب مرجعي لهذه الوثيقة — اصغ الوثيقة كاملة بناءً على نوعها ووصف الطلب وبيانات الأطراف الواردة في قسم DATA. استخدم البنية القانونية المعتادة لهذا النوع من الوثائق في الممارسة المصرية (الديباجة، تعريف الأطراف، الموضوع، البنود، التوقيع).

2. قسم REFERENCE (إن وُجد): مقاطع من عقود سابقة للمكتب لها صلة. استخدمها كنمط للأسلوب والصياغة، لا للنسخ الحرفي.

3. قسم CLAUSES (إن وُجد): بنود معتمدة من شريك. أدرجها حرفياً دون تعديل في الموضع المناسب.

4. ممنوع تماماً اختلاق أرقام مواد قانونية أو الاستشهاد بأحكام قضائية أو نصوص تشريعية برقم محدد. إذا احتجت لاستناد قانوني، اكتفِ بإشارة عامة مثل "وفقاً لأحكام القانون المدني المصري" بدون رقم، أو اكتب "[يتم تأكيد المرجع القانوني بواسطة المحامي]" واترك المحامي يستكمله.

5. استخدم بيانات الأطراف بدقة كما وردت (الاسم، الرقم القومي، العنوان، الجنسية، الديانة، المهنة) في القسم الخاص بكل طرف.

6. اكتب نصاً نهائياً نظيفاً قابلاً للتوثيق دون شطب أو تعديل بخط اليد. لا تستخدم رموز Markdown مثل `---` أو `**` — اكتب نصاً عربياً عادياً.

7. كل ناتج مولّد آلياً يُعرض على محامٍ للمراجعة قبل الاعتماد.
TXT;

        $system = $hasTemplate ? $systemWithTemplate : $systemFromScratch;

        // Pre-substitute predefined-catalog tokens directly into the template
        // body — `{{seller.name}}` → the actual name. What remains in the
        // body is either a truly-unfilled token (intentional, leave for review)
        // or `..........` style blanks that Claude can fill from DATA.
        $resolvedBody = $template?->body
            ? $this->tokens->replace($template->body, $filledData)
            : null;

        $templateBlock = $resolvedBody
            ? "TEMPLATE — قالب المكتب المعتمد. ابنِ المسودة عليه:\n```\n{$resolvedBody}\n```\n"
            : '';

        $reference = $retrievedChunks
            ->map(fn (array $c, int $i) => '--- مرجع #'.($i + 1)."\n".$c['chunk_text'])
            ->implode("\n\n");
        $referenceBlock = $reference !== ''
            ? "REFERENCE — مقاطع من عقود سابقة للمكتب، للأسلوب فقط:\n{$reference}\n"
            : '';

        // Resolve tokens inside clause bodies too — they may reference
        // {{seller.name}} or {{court.name}}.
        $clauses = $verbatimClauses
            ->map(function (ClauseVersion $cv, int $i) use ($filledData) {
                $body = $this->tokens->replace($cv->body, $filledData);

                return '--- بند #'.($i + 1)."\n".$body;
            })
            ->implode("\n\n");
        $clausesBlock = $clauses !== ''
            ? "CLAUSES — بنود معتمدة، أدرجها حرفياً في موقعها المناسب:\n{$clauses}\n"
            : '';

        $data = collect($filledData)
            ->filter(fn ($v) => $v !== null && $v !== '')
            ->map(fn ($v, $k) => "- {$k}: ".(is_scalar($v) ? (string) $v : json_encode($v, JSON_UNESCAPED_UNICODE)))
            ->implode("\n");
        $dataBlock = $data !== ''
            ? "DATA — قيم الحقول المعبأة (للمرجع — معظمها مُدمج بالفعل في TEMPLATE):\n{$data}\n"
            : "DATA — (لم تُملأ حقول. أبقِ العلامات النائبة كما هي حتى تُملأ.)\n";

        $userMessage = "المهمة: {$userIntent}\n\n{$templateBlock}\n{$referenceBlock}\n{$clausesBlock}\n{$dataBlock}\nأخرج النص الكامل للعقد جاهزاً للمراجعة من محامٍ. لا تضف عنواناً تمهيدياً ولا تعليقات شارحة، فقط نص العقد.";

        return [
            'system' => $system,
            'messages' => [
                ['role' => 'user', 'content' => $userMessage],
            ],
        ];
    }
}
