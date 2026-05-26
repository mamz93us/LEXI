<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets the firm upload an existing توكيل (PDF or JPG scan) and have
 * Claude extract the structured data so it can be reused when drafting
 * new documents.
 *
 * - file_path           storage-relative path of the uploaded original
 * - file_mime           "application/pdf" or "image/jpeg" etc
 * - extracted_text      raw text Claude saw / surfaced (for audit + debug)
 * - extracted_data      JSON of structured fields (principal, agent, …)
 * - extraction_status   pending | extracting | extracted | failed | null
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proxies', function (Blueprint $table) {
            $table->string('file_path')->nullable()->after('status');
            $table->string('file_mime', 100)->nullable()->after('file_path');
            $table->text('extracted_text')->nullable()->after('file_mime');
            $table->json('extracted_data')->nullable()->after('extracted_text');
            $table->string('extraction_status', 32)->nullable()->after('extracted_data');
        });
    }

    public function down(): void
    {
        Schema::table('proxies', function (Blueprint $table) {
            $table->dropColumn([
                'file_path',
                'file_mime',
                'extracted_text',
                'extracted_data',
                'extraction_status',
            ]);
        });
    }
};
