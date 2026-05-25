<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CourtType;
use Illuminate\Database\Seeder;

class CourtTypeSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['code' => 'partial', 'name_ar' => 'المحكمة الجزئية', 'name_en' => 'Partial (Summary) Court', 'sort_order' => 10],
            ['code' => 'first_instance', 'name_ar' => 'المحكمة الابتدائية', 'name_en' => 'Court of First Instance', 'sort_order' => 20],
            ['code' => 'appeal', 'name_ar' => 'محكمة الاستئناف', 'name_en' => 'Court of Appeal', 'sort_order' => 30],
            ['code' => 'cassation', 'name_ar' => 'محكمة النقض', 'name_en' => 'Court of Cassation', 'sort_order' => 40],
            ['code' => 'economic', 'name_ar' => 'المحكمة الاقتصادية', 'name_en' => 'Economic Court', 'sort_order' => 50],
            ['code' => 'family', 'name_ar' => 'محكمة الأسرة', 'name_en' => 'Family Court', 'sort_order' => 60],
            ['code' => 'administrative', 'name_ar' => 'القضاء الإداري (مجلس الدولة)', 'name_en' => 'Administrative Judiciary (Council of State)', 'sort_order' => 70],
            ['code' => 'criminal_misdemeanor', 'name_ar' => 'محكمة الجنح', 'name_en' => 'Misdemeanours Court', 'sort_order' => 80],
            ['code' => 'criminal_felony', 'name_ar' => 'محكمة الجنايات', 'name_en' => 'Felonies Court', 'sort_order' => 90],
            ['code' => 'constitutional', 'name_ar' => 'المحكمة الدستورية العليا', 'name_en' => 'Supreme Constitutional Court', 'sort_order' => 100],
        ];

        foreach ($rows as $row) {
            CourtType::withoutTenantScope()->updateOrCreate(
                ['tenant_id' => null, 'code' => $row['code']],
                $row,
            );
        }
    }
}
