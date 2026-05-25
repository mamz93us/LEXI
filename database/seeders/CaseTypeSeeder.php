<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CaseType;
use Illuminate\Database\Seeder;

class CaseTypeSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['code' => 'civil', 'name_ar' => 'مدني', 'name_en' => 'Civil', 'sort_order' => 10],
            ['code' => 'commercial', 'name_ar' => 'تجاري', 'name_en' => 'Commercial', 'sort_order' => 20],
            ['code' => 'economic', 'name_ar' => 'اقتصادي', 'name_en' => 'Economic', 'sort_order' => 30],
            ['code' => 'criminal_misdemeanor', 'name_ar' => 'جنح', 'name_en' => 'Misdemeanour', 'sort_order' => 40],
            ['code' => 'criminal_felony', 'name_ar' => 'جنايات', 'name_en' => 'Felony', 'sort_order' => 50],
            ['code' => 'labor', 'name_ar' => 'عمالي', 'name_en' => 'Labor', 'sort_order' => 60],
            ['code' => 'personal_status', 'name_ar' => 'أحوال شخصية', 'name_en' => 'Personal Status', 'sort_order' => 70],
            ['code' => 'family', 'name_ar' => 'أسرة', 'name_en' => 'Family', 'sort_order' => 80],
            ['code' => 'administrative', 'name_ar' => 'إداري', 'name_en' => 'Administrative', 'sort_order' => 90],
            ['code' => 'rent', 'name_ar' => 'إيجارات', 'name_en' => 'Rent', 'sort_order' => 100],
            ['code' => 'enforcement', 'name_ar' => 'تنفيذ', 'name_en' => 'Enforcement', 'sort_order' => 110],
            ['code' => 'summary_urgent', 'name_ar' => 'أمور مستعجلة', 'name_en' => 'Summary urgent matters', 'sort_order' => 120],
        ];

        foreach ($rows as $row) {
            CaseType::withoutTenantScope()->updateOrCreate(
                ['tenant_id' => null, 'code' => $row['code']],
                $row,
            );
        }
    }
}
