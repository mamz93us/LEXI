<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\JudgmentType;
use Illuminate\Database\Seeder;

class JudgmentTypeSeeder extends Seeder
{
    public function run(): void
    {
        // The brief lists تمهيدي / قطعي / حضوري / غيابي / بات. We split that:
        // - The 'legal class' lives in this table (preliminary / final / res_judicata)
        // - حضوري / غيابي live as a `presence_type` enum on `judgments`
        $rows = [
            ['code' => 'preliminary', 'name_ar' => 'تمهيدي', 'name_en' => 'Preliminary (interlocutory)', 'sort_order' => 10],
            ['code' => 'final', 'name_ar' => 'قطعي / نهائي', 'name_en' => 'Final', 'sort_order' => 20],
            ['code' => 'res_judicata', 'name_ar' => 'بات', 'name_en' => 'Incontestable (res judicata)', 'sort_order' => 30],
        ];

        foreach ($rows as $row) {
            JudgmentType::withoutTenantScope()->updateOrCreate(
                ['tenant_id' => null, 'code' => $row['code']],
                $row,
            );
        }
    }
}
