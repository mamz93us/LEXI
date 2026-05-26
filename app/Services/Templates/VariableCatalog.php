<?php

declare(strict_types=1);

namespace App\Services\Templates;

/**
 * Predefined variable catalog for templates and clauses.
 *
 * Templates use `{{namespace.field}}` tokens (e.g. `{{seller.name}}`,
 * `{{buyer.national_id}}`, `{{contract.place}}`). The catalog enumerates
 * which namespaces exist (parties + contract metadata), what fields each
 * one carries, and the Arabic labels shown in the editor's variable chip
 * sidebar.
 *
 * Why a fixed catalog: contract clauses must reference the same canonical
 * names across templates so a clause written for a "بيع" template can be
 * reused in a "إيجار" template without rewriting tokens by hand.
 */
final class VariableCatalog
{
    /**
     * Fields every contract party carries. Maps the field key (matches
     * Client::toAiVariables() output) to its Arabic label.
     *
     * @var array<string, string>
     */
    public const PARTY_FIELDS = [
        'name' => 'الاسم',
        'name_en' => 'الاسم بالإنجليزية',
        'national_id' => 'الرقم القومي',
        'commercial_register_no' => 'السجل التجاري',
        'address' => 'العنوان',
        'phone' => 'رقم الهاتف',
        'whatsapp' => 'رقم الواتساب',
        'email' => 'البريد الإلكتروني',
        'nationality' => 'الجنسية',
        'religion' => 'الديانة',
        'profession' => 'المهنة',
        'date_of_birth' => 'تاريخ الميلاد',
        'type' => 'النوع (فرد/شركة)',
    ];

    /**
     * Predefined party namespaces. Each entry has an Arabic label + a
     * human-readable role used in the picker UI.
     *
     * @var array<string, array{label_ar: string, role: string}>
     */
    public const PARTIES = [
        'seller' => ['label_ar' => 'البائع', 'role' => 'seller'],
        'buyer' => ['label_ar' => 'المشتري', 'role' => 'buyer'],
        'lessor' => ['label_ar' => 'المؤجر', 'role' => 'lessor'],
        'lessee' => ['label_ar' => 'المستأجر', 'role' => 'lessee'],
        'principal' => ['label_ar' => 'الموكِّل', 'role' => 'principal'],
        'agent' => ['label_ar' => 'الوكيل', 'role' => 'agent'],
        'employer' => ['label_ar' => 'صاحب العمل', 'role' => 'employer'],
        'employee' => ['label_ar' => 'العامل', 'role' => 'employee'],
        'party1' => ['label_ar' => 'الطرف الأول', 'role' => 'party1'],
        'party2' => ['label_ar' => 'الطرف الثاني', 'role' => 'party2'],
    ];

    /**
     * Contract / matter metadata fields not tied to a party. Each maps
     * `dotted.token` → ['label_ar' => ..., 'type' => text|date|select].
     * Some have a fixed source (e.g. court is picked from the seeded
     * courts table at draft time).
     *
     * @var array<string, array{label_ar: string, type: string, source?: string}>
     */
    public const CONTRACT_META = [
        'contract.place' => ['label_ar' => 'مكان تحرير العقد', 'type' => 'text'],
        'contract.date' => ['label_ar' => 'تاريخ تحرير العقد', 'type' => 'date'],
        'contract.value' => ['label_ar' => 'قيمة العقد', 'type' => 'text'],
        'contract.currency' => ['label_ar' => 'العملة', 'type' => 'text'],
        'contract.subject' => ['label_ar' => 'موضوع العقد', 'type' => 'textarea'],
        'contract.duration' => ['label_ar' => 'مدة العقد', 'type' => 'text'],
        'court.name' => ['label_ar' => 'المحكمة المختصة', 'type' => 'select', 'source' => 'courts'],
        'court.city' => ['label_ar' => 'مدينة المحكمة المختصة', 'type' => 'text'],
        'firm.name' => ['label_ar' => 'اسم المكتب', 'type' => 'text'],
        'today' => ['label_ar' => 'تاريخ اليوم', 'type' => 'date'],
    ];

    /**
     * Build the full list of party tokens for one namespace, e.g.
     * `seller` → `['seller.name', 'seller.national_id', ...]`.
     *
     * Each entry carries a pre-built `snippet` like `{{seller.name}}`.
     * IMPORTANT: the snippet is built here in PHP — Blade NEVER sees the
     * literal `{{` `}}` characters in template source, so it can't
     * misinterpret them as echo tags.
     *
     * @return array<int, array{token: string, label_ar: string, snippet: string}>
     */
    public static function partyTokens(string $namespace): array
    {
        $out = [];
        foreach (self::PARTY_FIELDS as $field => $label) {
            $token = "{$namespace}.{$field}";
            $out[] = [
                'token' => $token,
                'label_ar' => $label,
                'snippet' => '{{'.$token.'}}',
            ];
        }

        return $out;
    }

    /**
     * Return every group of tokens for the editor sidebar:
     *   [
     *     ['heading' => 'البائع', 'tokens' => [...]],
     *     ['heading' => 'المشتري', 'tokens' => [...]],
     *     ['heading' => 'بيانات العقد', 'tokens' => [...]],
     *   ]
     *
     * @return array<int, array{heading: string, tokens: array<int, array{token: string, label_ar: string}>}>
     */
    public static function groupTokens(): array
    {
        $groups = [];

        foreach (self::PARTIES as $ns => $meta) {
            $groups[] = [
                'heading' => $meta['label_ar'],
                'tokens' => self::partyTokens($ns),
            ];
        }

        $contractTokens = [];
        foreach (self::CONTRACT_META as $token => $meta) {
            $contractTokens[] = [
                'token' => $token,
                'label_ar' => $meta['label_ar'],
                'snippet' => '{{'.$token.'}}',
            ];
        }
        $groups[] = [
            'heading' => 'بيانات العقد',
            'tokens' => $contractTokens,
        ];

        return $groups;
    }

    /**
     * Find which predefined party namespaces are actually referenced in a
     * template body, by scanning for `{{namespace.anything}}` tokens.
     *
     * @return array<int, string> e.g. ['seller', 'buyer']
     */
    public static function detectPartiesInTemplate(string $body): array
    {
        preg_match_all('/\{\{\s*([a-zA-Z_][a-zA-Z0-9_]*)\.[a-zA-Z_][a-zA-Z0-9_]*\s*\}\}/', $body, $matches);
        $namespaces = array_unique($matches[1] ?? []);

        return array_values(array_filter(
            $namespaces,
            fn (string $ns) => array_key_exists($ns, self::PARTIES),
        ));
    }

    /**
     * Find which contract-meta tokens are referenced in a template body.
     *
     * @return array<int, string> e.g. ['contract.place', 'court.name']
     */
    public static function detectContractMetaInTemplate(string $body): array
    {
        preg_match_all('/\{\{\s*([a-zA-Z_][a-zA-Z0-9_]*(?:\.[a-zA-Z_][a-zA-Z0-9_]*)*)\s*\}\}/', $body, $matches);
        $tokens = array_unique($matches[1] ?? []);

        return array_values(array_filter(
            $tokens,
            fn (string $t) => array_key_exists($t, self::CONTRACT_META),
        ));
    }

    public static function partyLabel(string $namespace): string
    {
        return self::PARTIES[$namespace]['label_ar'] ?? $namespace;
    }
}
