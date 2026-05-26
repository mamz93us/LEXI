<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add the Egyptian-legal-contract fields a client typically needs:
 * nationality, religion, profession, date_of_birth. These plug into
 * the predefined variable catalog ({{seller.nationality}}, etc.).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('nationality')->default('مصري')->after('national_id');
            $table->string('religion')->nullable()->after('nationality');
            $table->string('profession')->nullable()->after('religion');
            $table->date('date_of_birth')->nullable()->after('profession');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['nationality', 'religion', 'profession', 'date_of_birth']);
        });
    }
};
