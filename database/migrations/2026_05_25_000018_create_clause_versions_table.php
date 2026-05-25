<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clause_versions', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->foreignId('clause_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version_no');
            $table->longText('body');
            // Whitelist-evaluator expression — keys + operators only, never `eval()`.
            $table->json('condition_expression')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')
                ->references('id')->on('tenants')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->unique(['clause_id', 'version_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clause_versions');
    }
};
