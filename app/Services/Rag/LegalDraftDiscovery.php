<?php

declare(strict_types=1);

namespace App\Services\Rag;

use App\Services\Ai\AnthropicClient;
use App\Services\Templates\DocumentTypeRegistry;
use App\Services\Templates\VariableCatalog;
use RuntimeException;

/**
 * Phase 1 of the "from-scratch" drafter: given a document type + the
 * lawyer's free-text description, ask Claude what data it needs to
 * actually draft the document. Claude returns a structured JSON spec:
 *
 *   {
 *     "parties":  [{namespace, label_ar}],
 *     "fields":   [{key, label_ar, type, required}],
 *     "clauses_to_consider": [{topic, label_ar}],
 *     "lawyer_warnings": [string, ...]
 *   }
 *
 * The wizard then renders a form from this spec, the lawyer fills it,
 * and Phase 2 (RagGenerator) does the actual draft as a queued job.
 *
 * Why a discovery step at all: every legal document type has different
 * required data (a sale needs property details, a POA doesn't). Asking
 * the AI to surface those fields up-front lets the lawyer fill them in
 * one screen instead of doing 3 rounds of "Claude, please add X."
 *
 * Discovery is a small, fast Claude call — it returns ~300-500 tokens of
 * JSON in 5-15s. So it's done synchronously inside the Livewire request;
 * only the much-longer drafting phase is queued.
 */
final class LegalDraftDiscovery
{
    public function __construct(
        private readonly AnthropicClient $anthropic,
    ) {}

    /**
     * @param  array<string, mixed>  $existingData  flat dotted-key map of values
     *                                              the lawyer ALREADY has (e.g.
     *                                              from a linked proxy). Claude
     *                                              is told to skip these.
     * @return array{
     *     parties: array<int, array{namespace: string, label_ar: string}>,
     *     fields: array<int, array{key: string, label_ar: string, type: string, required: bool}>,
     *     clauses_to_consider: array<int, array{topic: string, label_ar: string}>,
     *     lawyer_warnings: array<int, string>,
     *     raw: string
     * }
     */
    public function discover(string $docType, string $description, array $existingData = []): array
    {
        $typeMeta = DocumentTypeRegistry::get($docType);
        if (! $typeMeta) {
            throw new RuntimeException("Unknown document type: {$docType}");
        }

        $knownParties = implode(', ', array_keys(VariableCatalog::PARTIES));
        $defaultParties = $typeMeta['parties'] ? implode(', ', $typeMeta['parties']) : '(none)';
        $defaultMeta = implode(', ', $typeMeta['contract_meta']);

        $existingBlock = '';
        if (! empty($existingData)) {
            $lines = collect($existingData)
                ->filter(fn ($v) => $v !== null && $v !== '')
                ->map(fn ($v, $k) => "- {$k}: ".(is_scalar($v) ? (string) $v : json_encode($v, JSON_UNESCAPED_UNICODE)))
                ->implode("\n");
            $existingBlock = "\n\nبيانات متوفرة لديّ بالفعل (من توكيل سابق مرفق أو مصدر آخر) — لا تطلب هذه الحقول مرة أخرى:\n{$lines}\n";
        }

        $system = <<<TXT
أنت مساعد صياغة قانوني مصري متخصص. مهمتك في هذه المرحلة هي تحديد البيانات اللازمة لصياغة وثيقة قانونية، وليس صياغتها.

أعد الإجابة كـ JSON صالح فقط، بدون أي شرح خارجي أو رموز Markdown.

الصيغة المطلوبة:
{
  "parties": [
    {"namespace": "<one of: {$knownParties}>", "label_ar": "<arabic label, e.g. البائع>"}
  ],
  "fields": [
    {"key": "<dotted.key>", "label_ar": "<arabic label>", "type": "<text|textarea|date|number>", "required": <true|false>}
  ],
  "clauses_to_consider": [
    {"topic": "<short topic key>", "label_ar": "<arabic label>"}
  ],
  "lawyer_warnings": [
    "<arabic warning the lawyer should verify, e.g. تأكد من تطابق الرقم القومي مع البطاقة>"
  ]
}

قواعد صارمة:
1. parties.namespace يجب أن يكون من هذه القائمة فقط: {$knownParties}.
2. لا تكرر الحقول التي ستأتي من بيانات الطرف (الاسم، الرقم القومي، العنوان، إلخ) — هذه مدمجة تلقائياً عند اختيار العميل.
3. أضف فقط الحقول الخاصة بهذه الوثيقة (مثلاً للبيع: مواصفات العقار، رقم العقار، طريقة السداد. للإيجار: المدة، الإيجار الشهري).
4. اقتصر على حقول مفيدة فعلاً — لا تطلب بيانات يمكن استنتاجها أو لا حاجة لها في هذا النوع من الوثيقة.
5. lawyer_warnings: 2-4 ملاحظات قانونية يجب أن يراجعها المحامي قبل الاعتماد (مثل التحقق من تطابق البيانات، أو وجود حقوق طرف ثالث).
TXT;

        $user = <<<TXT
نوع الوثيقة المطلوب صياغتها: {$typeMeta['label_ar']}
الأطراف المتوقعة افتراضياً: {$defaultParties}
بيانات العقد المتوقعة افتراضياً: {$defaultMeta}

وصف الطلب من المحامي:
{$description}
{$existingBlock}
حدد ما تحتاجه فعلاً لصياغة هذه الوثيقة كـ JSON. اقتصر على الحقول الإضافية التي لم تُذكر بالفعل في "البيانات المتوفرة" أعلاه.
TXT;

        $raw = $this->anthropic->sendMessages($system, [
            ['role' => 'user', 'content' => $user],
        ]);

        $parsed = $this->parseJson($raw);

        return [
            'parties' => $this->normalizeParties($parsed['parties'] ?? []),
            'fields' => $this->normalizeFields($parsed['fields'] ?? []),
            'clauses_to_consider' => $this->normalizeClauses($parsed['clauses_to_consider'] ?? []),
            'lawyer_warnings' => array_values(array_filter(
                array_map('strval', $parsed['lawyer_warnings'] ?? []),
                fn (string $w) => trim($w) !== '',
            )),
            'raw' => $raw,
        ];
    }

    /**
     * Strip any wrapping ```json fences and decode. Claude sometimes
     * leaks chat-style preambles even with strict instructions — we
     * locate the first `{` and last `}` to extract the JSON body.
     */
    private function parseJson(string $raw): array
    {
        $start = strpos($raw, '{');
        $end = strrpos($raw, '}');
        if ($start === false || $end === false || $end <= $start) {
            throw new RuntimeException('Claude discovery did not return JSON. Raw: '.substr($raw, 0, 400));
        }
        $json = substr($raw, $start, $end - $start + 1);
        $decoded = json_decode($json, true);
        if (! is_array($decoded)) {
            throw new RuntimeException('Claude discovery JSON could not be parsed. Raw: '.substr($raw, 0, 400));
        }

        return $decoded;
    }

    private function normalizeParties(array $parties): array
    {
        $known = array_keys(VariableCatalog::PARTIES);
        $out = [];
        foreach ($parties as $p) {
            $ns = (string) ($p['namespace'] ?? '');
            if (! in_array($ns, $known, true)) {
                continue;
            }
            $out[] = [
                'namespace' => $ns,
                'label_ar' => (string) ($p['label_ar'] ?? VariableCatalog::partyLabel($ns)),
            ];
        }

        return $out;
    }

    private function normalizeFields(array $fields): array
    {
        $allowedTypes = ['text', 'textarea', 'date', 'number'];
        $out = [];
        foreach ($fields as $f) {
            $key = trim((string) ($f['key'] ?? ''));
            if ($key === '') {
                continue;
            }
            // Sanitize the key — only allow dotted identifier syntax so the
            // token never collides with Blade or with a stray PHP expression.
            if (! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*(?:\.[a-zA-Z_][a-zA-Z0-9_]*)*$/', $key)) {
                continue;
            }
            $type = (string) ($f['type'] ?? 'text');
            if (! in_array($type, $allowedTypes, true)) {
                $type = 'text';
            }
            $out[] = [
                'key' => $key,
                'label_ar' => (string) ($f['label_ar'] ?? $key),
                'type' => $type,
                'required' => (bool) ($f['required'] ?? false),
            ];
        }

        return $out;
    }

    private function normalizeClauses(array $clauses): array
    {
        $out = [];
        foreach ($clauses as $c) {
            $topic = trim((string) ($c['topic'] ?? ''));
            if ($topic === '') {
                continue;
            }
            $out[] = [
                'topic' => $topic,
                'label_ar' => (string) ($c['label_ar'] ?? $topic),
            ];
        }

        return $out;
    }
}
