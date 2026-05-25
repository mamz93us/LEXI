<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version_no');
            // Generation source — we store the recipe, never just the blob.
            $table->unsignedBigInteger('template_version_id')->nullable();
            $table->json('clause_version_ids')->nullable();
            $table->json('filled_data')->nullable();
            $table->string('storage_ref')->nullable();      // s3 key for DOCX
            $table->string('pdf_storage_ref')->nullable();  // s3 key for PDF
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('locked')->default(false);
            $table->timestamps();

            $table->foreign('tenant_id')
                ->references('id')->on('tenants')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->unique(['document_id', 'version_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_versions');
    }
};
