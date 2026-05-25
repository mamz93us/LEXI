<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\RequestType;
use Illuminate\Database\Seeder;

class RequestTypeSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['code' => 'postponement', 'name_ar' => 'طلب تأجيل', 'name_en' => 'Postponement', 'sort_order' => 10],
            ['code' => 'submit_memo', 'name_ar' => 'تقديم مذكرة', 'name_en' => 'Submit memorandum', 'sort_order' => 20],
            ['code' => 'join_docs', 'name_ar' => 'ضم مستندات', 'name_en' => 'Join documents to file', 'sort_order' => 30],
            ['code' => 'expert_request', 'name_ar' => 'ندب خبير', 'name_en' => 'Appoint an expert', 'sort_order' => 40],
            ['code' => 'witness_hearing', 'name_ar' => 'سماع شهود', 'name_en' => 'Hear witnesses', 'sort_order' => 50],
            ['code' => 'interim_relief', 'name_ar' => 'طلب مستعجل', 'name_en' => 'Interim / urgent relief', 'sort_order' => 60],
            ['code' => 'reserve_for_judgment', 'name_ar' => 'حجز للحكم', 'name_en' => 'Reserve for judgment', 'sort_order' => 70],
            ['code' => 'reopen_pleadings', 'name_ar' => 'إعادة المرافعة', 'name_en' => 'Reopen pleadings', 'sort_order' => 80],
            ['code' => 'dismissal', 'name_ar' => 'طلب رفض', 'name_en' => 'Request dismissal', 'sort_order' => 90],
            ['code' => 'acceptance', 'name_ar' => 'طلب قبول', 'name_en' => 'Request acceptance', 'sort_order' => 100],
        ];

        foreach ($rows as $row) {
            RequestType::withoutTenantScope()->updateOrCreate(
                ['tenant_id' => null, 'code' => $row['code']],
                $row,
            );
        }
    }
}
