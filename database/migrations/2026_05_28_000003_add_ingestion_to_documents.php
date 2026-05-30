<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RAG ingestion status on documents. When a document version is uploaded,
 * IngestDocumentJob extracts text → normalises → chunks → embeds → writes
 * contract_embeddings rows. These columns surface progress + result.
 *
 * - ingestion_status: null | pending | ingesting | ingested | skipped | failed
 * - embedding_count:  how many chunks were embedded
 * - ingestion_note:   short reason on skipped/failed (e.g. OCR quality gate)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('ingestion_status', 32)->nullable()->after('ocr_text');
            $table->unsignedInteger('embedding_count')->default(0)->after('ingestion_status');
            $table->string('ingestion_note')->nullable()->after('embedding_count');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['ingestion_status', 'embedding_count', 'ingestion_note']);
        });
    }
};
