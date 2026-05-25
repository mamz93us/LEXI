<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Vector store table for tenant-scoped RAG retrieval.
 *
 * Postgres path uses the `vector` type (pgvector). SQLite path keeps the
 * column as BLOB/text so the test suite can run without pgvector — vector
 * search itself only runs against Postgres.
 *
 * The vector dimension is read from config('lexa.embeddings.dimension')
 * and falls back to 1024 (Cohere multilingual default).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_embeddings', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->foreignId('document_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('source_version_id')->nullable()->constrained('document_versions')->nullOnDelete();
            $table->unsignedInteger('chunk_index');
            $table->longText('chunk_text');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')
                ->references('id')->on('tenants')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->index(['tenant_id', 'document_id']);
        });

        if (DB::getDriverName() === 'pgsql') {
            $dim = (int) config('lexa.embeddings.dimension', 1024);
            DB::statement("ALTER TABLE contract_embeddings ADD COLUMN embedding vector({$dim})");
            // Vector index is created lazily once meaningful data is loaded —
            // ivfflat / hnsw choice requires the row count to tune lists.
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_embeddings');
    }
};
