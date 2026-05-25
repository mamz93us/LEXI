<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Milestone 1.2 — extend `cases` with Egyptian litigation fields:
 * degree, court_id, case_type_id, circuit, roll_no, parent_case_id (self-ref
 * for طعون chain), appeal_type, parties JSON.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->string('degree')->default('first_instance')->after('title');
            $table->foreignId('court_id')->nullable()->after('degree')->constrained()->nullOnDelete();
            $table->foreignId('case_type_id')->nullable()->after('court_id')->constrained()->nullOnDelete();
            $table->string('circuit')->nullable()->after('case_type_id'); // الدائرة
            $table->string('roll_no')->nullable()->after('circuit');      // رقم الرول
            $table->foreignId('parent_case_id')->nullable()->after('roll_no')
                ->constrained('cases')->nullOnDelete();
            $table->string('appeal_type')->nullable()->after('parent_case_id');
            $table->json('parties')->nullable()->after('appeal_type');
        });
    }

    public function down(): void
    {
        Schema::table('cases', function (Blueprint $table) {
            $table->dropConstrainedForeignId('court_id');
            $table->dropConstrainedForeignId('case_type_id');
            $table->dropConstrainedForeignId('parent_case_id');
            $table->dropColumn(['degree', 'circuit', 'roll_no', 'appeal_type', 'parties']);
        });
    }
};
