<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lawyer-relevant identity fields on users so a firm lawyer can stand
 * in for a Client as a contract / proxy party (الوكيل في توكيل خاص
 * بحضور جلسات for instance).
 *
 * - name_ar              Arabic name as it should appear in contracts
 * - national_id          الرقم القومي — required to identify a person legally
 * - address              العنوان
 * - nationality          الجنسية (defaults to مصري)
 * - bar_association_no   رقم نقابة المحامين
 * - is_active            soft "deactivated" flag — keep history, prevent login
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name_ar')->nullable()->after('name');
            $table->string('national_id', 32)->nullable()->after('phone');
            $table->string('bar_association_no', 64)->nullable()->after('national_id');
            $table->string('nationality')->default('مصري')->after('bar_association_no');
            $table->text('address')->nullable()->after('nationality');
            $table->boolean('is_active')->default(true)->after('address');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'name_ar',
                'national_id',
                'bar_association_no',
                'nationality',
                'address',
                'is_active',
            ]);
        });
    }
};
