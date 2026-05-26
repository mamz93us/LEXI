<?php

declare(strict_types=1);

namespace App\Services\Templates;

/**
 * Catalogue of legal document types the firm can draft from scratch
 * (without an existing Template). Each entry hints which party namespaces
 * and contract-meta tokens are typically needed — those are pre-shown in
 * the wizard so the lawyer can fill them quickly even before the AI
 * discovery step runs (or instead of it, if discovery fails).
 *
 * Keep this list focused on Egyptian legal practice. Add new entries
 * here rather than letting users free-form the doc-type — the registry
 * is what powers the wizard's first dropdown.
 */
final class DocumentTypeRegistry
{
    /**
     * @var array<string, array{
     *     label_ar: string,
     *     label_en: string,
     *     category: string,
     *     parties: array<int, string>,
     *     contract_meta: array<int, string>,
     *     description_placeholder: string
     * }>
     */
    public const TYPES = [
        'sale_contract' => [
            'label_ar' => 'عقد بيع',
            'label_en' => 'Sale contract',
            'category' => 'عقود',
            'parties' => ['seller', 'buyer'],
            'contract_meta' => ['contract.place', 'contract.date', 'contract.value', 'contract.subject', 'court.name'],
            'description_placeholder' => 'مثال: بيع شقة في المعادي مساحتها 150 متر بقيمة 3,500,000 جنيه. التسليم خلال 30 يوماً من توقيع العقد. السداد على دفعتين.',
        ],
        'rent_contract' => [
            'label_ar' => 'عقد إيجار',
            'label_en' => 'Rent contract',
            'category' => 'عقود',
            'parties' => ['lessor', 'lessee'],
            'contract_meta' => ['contract.place', 'contract.date', 'contract.value', 'contract.duration', 'contract.subject', 'court.name'],
            'description_placeholder' => 'مثال: إيجار محل تجاري في الجيزة لمدة 3 سنوات بإيجار شهري 15,000 جنيه. الزيادة السنوية 7%. التأمين 3 شهور مقدماً.',
        ],
        'employment_contract' => [
            'label_ar' => 'عقد عمل',
            'label_en' => 'Employment contract',
            'category' => 'عقود',
            'parties' => ['employer', 'employee'],
            'contract_meta' => ['contract.place', 'contract.date', 'contract.duration', 'contract.value', 'contract.subject'],
            'description_placeholder' => 'مثال: تعيين مهندس برمجيات براتب شهري 25,000 جنيه. عقد محدد المدة سنة قابلة للتجديد. فترة تجربة 3 شهور.',
        ],
        'partnership_contract' => [
            'label_ar' => 'عقد شراكة',
            'label_en' => 'Partnership contract',
            'category' => 'عقود',
            'parties' => ['party1', 'party2'],
            'contract_meta' => ['contract.place', 'contract.date', 'contract.value', 'contract.subject', 'contract.duration'],
            'description_placeholder' => 'مثال: شراكة بنسبة 60/40 لتأسيس شركة في مجال التجارة. رأس المال مليون جنيه. مدة الشراكة 5 سنوات.',
        ],
        'general_poa' => [
            'label_ar' => 'توكيل عام',
            'label_en' => 'General power of attorney',
            'category' => 'توكيلات',
            'parties' => ['principal', 'agent'],
            'contract_meta' => ['contract.place', 'contract.date'],
            'description_placeholder' => 'مثال: توكيل عام في إدارة أعمال الموكل وممتلكاته والتعامل مع الجهات الحكومية والبنوك.',
        ],
        'special_poa' => [
            'label_ar' => 'توكيل خاص',
            'label_en' => 'Special power of attorney',
            'category' => 'توكيلات',
            'parties' => ['principal', 'agent'],
            'contract_meta' => ['contract.place', 'contract.date', 'contract.subject'],
            'description_placeholder' => 'مثال: توكيل خاص ببيع قطعة الأرض رقم 12 منطقة 6 أكتوبر. أو: توكيل خاص بحضور جلسات القضية رقم 1234/2025.',
        ],
        'court_memo' => [
            'label_ar' => 'مذكرة قانونية',
            'label_en' => 'Court memorandum',
            'category' => 'مرافعات',
            'parties' => [],
            'contract_meta' => ['contract.date', 'court.name', 'contract.subject'],
            'description_placeholder' => 'مثال: مذكرة بدفاع المدعى عليه في القضية رقم 1234/2025 إيجارات كلي القاهرة. الدفوع: عدم القبول، الانقضاء بالتقادم، عدم الجدية.',
        ],
        'complaint' => [
            'label_ar' => 'شكوى',
            'label_en' => 'Complaint',
            'category' => 'مرافعات',
            'parties' => ['party1', 'party2'],
            'contract_meta' => ['contract.place', 'contract.date', 'court.name', 'contract.subject'],
            'description_placeholder' => 'مثال: شكوى بتعطيل تنفيذ حكم. أو: شكوى إدارية ضد جهة حكومية بسبب امتناع عن إصدار ترخيص.',
        ],
        'legal_notice' => [
            'label_ar' => 'إنذار / إخطار',
            'label_en' => 'Legal notice',
            'category' => 'إخطارات',
            'parties' => ['party1', 'party2'],
            'contract_meta' => ['contract.date', 'contract.subject'],
            'description_placeholder' => 'مثال: إنذار بفسخ عقد إيجار لعدم سداد الإيجار. أو: إنذار بإخلاء عقار. أو: إخطار بإنهاء عقد عمل.',
        ],
        'declaration' => [
            'label_ar' => 'إقرار',
            'label_en' => 'Declaration',
            'category' => 'أخرى',
            'parties' => ['party1'],
            'contract_meta' => ['contract.date', 'contract.place', 'contract.subject'],
            'description_placeholder' => 'مثال: إقرار باستلام مبلغ 100,000 جنيه. أو: إقرار بالعلم بشروط البيع.',
        ],
        'settlement_agreement' => [
            'label_ar' => 'اتفاقية تصالح',
            'label_en' => 'Settlement agreement',
            'category' => 'عقود',
            'parties' => ['party1', 'party2'],
            'contract_meta' => ['contract.place', 'contract.date', 'contract.value', 'contract.subject', 'court.name'],
            'description_placeholder' => 'مثال: تصالح في النزاع رقم 567/2024 على دفع تعويض 500,000 جنيه مقابل إسقاط الدعوى.',
        ],
        'custom' => [
            'label_ar' => 'وثيقة قانونية أخرى',
            'label_en' => 'Other legal document',
            'category' => 'أخرى',
            'parties' => ['party1', 'party2'],
            'contract_meta' => ['contract.date', 'contract.place', 'contract.subject', 'court.name'],
            'description_placeholder' => 'صف نوع الوثيقة المطلوبة بالتفصيل (اسم الوثيقة، الأطراف، الموضوع، الغرض القانوني).',
        ],
    ];

    /** Lookup a type by key, or null if unknown. */
    public static function get(string $key): ?array
    {
        return self::TYPES[$key] ?? null;
    }

    /** All entries grouped by category for the dropdown UI. */
    public static function grouped(): array
    {
        $out = [];
        foreach (self::TYPES as $key => $meta) {
            $out[$meta['category']][$key] = $meta;
        }

        return $out;
    }

    public static function label(string $key): string
    {
        return self::TYPES[$key]['label_ar'] ?? $key;
    }
}
