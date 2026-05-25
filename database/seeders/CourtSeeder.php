<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Court;
use App\Models\CourtType;
use Illuminate\Database\Seeder;

class CourtSeeder extends Seeder
{
    public function run(): void
    {
        $appealType = CourtType::withoutTenantScope()->where('code', 'appeal')->first();

        if (! $appealType) {
            return;
        }

        $appealSeats = [
            ['name_ar' => 'استئناف القاهرة', 'name_en' => 'Cairo Appeal Court', 'governorate' => 'القاهرة', 'sort_order' => 10],
            ['name_ar' => 'استئناف الإسكندرية', 'name_en' => 'Alexandria Appeal Court', 'governorate' => 'الإسكندرية', 'sort_order' => 20],
            ['name_ar' => 'استئناف طنطا', 'name_en' => 'Tanta Appeal Court', 'governorate' => 'الغربية', 'sort_order' => 30],
            ['name_ar' => 'استئناف المنصورة', 'name_en' => 'Mansoura Appeal Court', 'governorate' => 'الدقهلية', 'sort_order' => 40],
            ['name_ar' => 'استئناف أسيوط', 'name_en' => 'Asyut Appeal Court', 'governorate' => 'أسيوط', 'sort_order' => 50],
            ['name_ar' => 'استئناف الإسماعيلية', 'name_en' => 'Ismailia Appeal Court', 'governorate' => 'الإسماعيلية', 'sort_order' => 60],
            ['name_ar' => 'استئناف بني سويف', 'name_en' => 'Bani Suef Appeal Court', 'governorate' => 'بني سويف', 'sort_order' => 70],
            ['name_ar' => 'استئناف قنا', 'name_en' => 'Qena Appeal Court', 'governorate' => 'قنا', 'sort_order' => 80],
        ];

        foreach ($appealSeats as $seat) {
            Court::withoutTenantScope()->updateOrCreate(
                ['tenant_id' => null, 'court_type_id' => $appealType->id, 'name_ar' => $seat['name_ar']],
                array_merge($seat, ['court_type_id' => $appealType->id]),
            );
        }
    }
}
