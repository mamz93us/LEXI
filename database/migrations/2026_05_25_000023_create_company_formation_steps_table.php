<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_formation_steps', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('step_order');
            $table->string('title');
            $table->string('authority')->nullable(); // GAFI / Tax Authority / مكتب السجل التجاري
            $table->string('status')->default('pending'); // pending | in_progress | done | blocked
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->bigInteger('fees_piastres')->nullable();
            $table->date('expected_date')->nullable();
            $table->date('actual_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')
                ->references('id')->on('tenants')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->index(['company_id', 'step_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_formation_steps');
    }
};
