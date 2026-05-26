<?php

declare(strict_types=1);

namespace App\Services\Proxies;

use App\Models\Proxy;
use App\Services\Ai\AnthropicClient;
use App\Services\Templates\VariableCatalog;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Reads an uploaded existing توكيل (PDF or JPG) and asks Claude to
 * extract the structured fields a lawyer would otherwise have to re-type:
 *
 *   - principal + agent identity data (name, national ID, address …)
 *   - notary serial, issue date, expiry date
 *   - the scope / authorities granted
 *   - witnesses, if any
 *   - the raw extracted text (for audit)
 *
 * Why Claude instead of Tesseract + a parser: Claude 3.5+ Sonnet and
 * Opus 4+ accept PDF and image inputs natively and OCR them internally,
 * including Arabic. So we skip needing tesseract / pdftotext binaries on
 * the server and get a structured-output answer in one round trip.
 *
 * The proxy row is updated in place — `extracted_text`, `extracted_data`,
 * `extraction_status`. Any party identity fields surfaced by Claude end
 * up under `extracted_data.parties.principal`, `.agent`, etc., matching
 * VariableCatalog::PARTY_FIELDS so Proxy::toAiVariables() can flatten
 * them into dotted tokens for the drafter.
 */
final class ProxyDataExtractor
{
    public function __construct(
        private readonly AnthropicClient $anthropic,
    ) {}

    public function extract(Proxy $proxy): array
    {
        if (! $proxy->file_path) {
            throw new RuntimeException('Proxy has no uploaded file to extract from.');
        }

        $disk = Storage::disk(config('filesystems.default'));
        if (! $disk->exists($proxy->file_path)) {
            throw new RuntimeException("Proxy file not found in storage: {$proxy->file_path}");
        }

        $bytes = (string) $disk->get($proxy->file_path);
        $mime = $proxy->file_mime ?: $this->guessMime($proxy->file_path);

        $knownParties = implode(', ', array_keys(VariableCatalog::PARTIES));
        $knownFields = implode(', ', array_keys(VariableCatalog::PARTY_FIELDS));

        $system = <<<TXT
أنت مساعد قانوني متخصص في قراءة الوثائق العربية. مهمتك هي استخراج البيانات المنظمة من توكيل قانوني مصري ممسوح ضوئياً أو ملف PDF.

أعد الإجابة كـ JSON صالح فقط، بدون أي شرح خارجي أو رموز Markdown.

الصيغة المطلوبة:
{
  "proxy": {
    "type": "general | specific",
    "notary_serial": "<رقم التوثيق إن وُجد>",
    "issue_date": "YYYY-MM-DD",
    "expiry_date": "YYYY-MM-DD or null",
    "scope": "<وصف موضوع التوكيل والصلاحيات>",
    "notary_office": "<اسم مكتب الشهر العقاري إن ظهر>"
  },
  "parties": {
    "principal": {
      "name": "...", "national_id": "...", "nationality": "...",
      "religion": "...", "profession": "...", "address": "...",
      "date_of_birth": "YYYY-MM-DD or null"
    },
    "agent": { ... same fields ... }
  },
  "witnesses": [
    {"name": "...", "national_id": "..."}
  ],
  "subject_property": "<وصف العقار أو الموضوع محل التوكيل إن وُجد، أو null>",
  "raw_text": "<النص الكامل للوثيقة كما قرأتها>"
}

قواعد:
1. parties.* keys must come from this set only: {$knownParties}. للتوكيل: استخدم principal للموكِّل و agent للوكيل.
2. parties.<ns>.* fields must come from this set only: {$knownFields}. اترك الحقل null إذا لم يظهر في الوثيقة.
3. proxy.type: استنتج إذا كان التوكيل عاماً (يشمل إدارة عامة) أو خاصاً (مقصور على غرض محدد).
4. التواريخ بالتقويم الميلادي بصيغة YYYY-MM-DD. حول من الهجري إذا لزم.
5. raw_text: اكتب النص العربي كاملاً كما قرأته من الوثيقة — للمراجعة من المحامي.
6. لا تختلق بيانات غير موجودة في الوثيقة. اترك null لما لا تستطيع قراءته بثقة.
TXT;

        $user = 'استخرج البيانات المنظمة من توكيل القانوني المرفق.';

        $raw = $this->anthropic->sendMessagesWithFiles($system, $user, [
            ['data' => $bytes, 'mime' => $mime],
        ]);

        $parsed = $this->parseJson($raw);

        return [
            'extracted_text' => (string) ($parsed['raw_text'] ?? ''),
            'extracted_data' => [
                'proxy' => $this->normalizeProxyMeta($parsed['proxy'] ?? []),
                'parties' => $this->normalizeParties($parsed['parties'] ?? []),
                'witnesses' => $this->normalizeWitnesses($parsed['witnesses'] ?? []),
                'subject_property' => $parsed['subject_property'] ?? null,
            ],
        ];
    }

    private function guessMime(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($ext) {
            'pdf' => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            default => 'application/octet-stream',
        };
    }

    private function parseJson(string $raw): array
    {
        $start = strpos($raw, '{');
        $end = strrpos($raw, '}');
        if ($start === false || $end === false || $end <= $start) {
            throw new RuntimeException('Claude proxy-extract did not return JSON. Raw: '.substr($raw, 0, 400));
        }
        $json = substr($raw, $start, $end - $start + 1);
        $decoded = json_decode($json, true);
        if (! is_array($decoded)) {
            throw new RuntimeException('Claude proxy-extract JSON could not be parsed. Raw: '.substr($raw, 0, 400));
        }

        return $decoded;
    }

    private function normalizeProxyMeta(array $meta): array
    {
        return [
            'type' => in_array(($meta['type'] ?? null), ['general', 'specific'], true) ? $meta['type'] : null,
            'notary_serial' => $this->str($meta['notary_serial'] ?? null),
            'issue_date' => $this->date($meta['issue_date'] ?? null),
            'expiry_date' => $this->date($meta['expiry_date'] ?? null),
            'scope' => $this->str($meta['scope'] ?? null),
            'notary_office' => $this->str($meta['notary_office'] ?? null),
        ];
    }

    private function normalizeParties(array $parties): array
    {
        $known = array_keys(VariableCatalog::PARTIES);
        $allowedFields = array_keys(VariableCatalog::PARTY_FIELDS);
        $out = [];
        foreach ($parties as $ns => $fields) {
            if (! is_string($ns) || ! in_array($ns, $known, true) || ! is_array($fields)) {
                continue;
            }
            $row = [];
            foreach ($fields as $key => $value) {
                if (! in_array($key, $allowedFields, true)) {
                    continue;
                }
                $row[$key] = $this->str($value);
            }
            if (! empty($row)) {
                $out[$ns] = $row;
            }
        }

        return $out;
    }

    private function normalizeWitnesses(array $witnesses): array
    {
        $out = [];
        foreach ($witnesses as $w) {
            if (! is_array($w)) {
                continue;
            }
            $out[] = [
                'name' => $this->str($w['name'] ?? null),
                'national_id' => $this->str($w['national_id'] ?? null),
            ];
        }

        return $out;
    }

    private function str(mixed $v): ?string
    {
        if ($v === null || $v === '' || ! is_scalar($v)) {
            return null;
        }
        $s = trim((string) $v);

        return $s === '' ? null : $s;
    }

    private function date(mixed $v): ?string
    {
        $s = $this->str($v);
        if ($s === null) {
            return null;
        }

        // Accept ISO YYYY-MM-DD only; reject anything else so we don't store garbage.
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $s) ? $s : null;
    }
}
