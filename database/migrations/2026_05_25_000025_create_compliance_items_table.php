<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compliance_items', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // cr_renewal | vat | tax | social_insurance | agm | auditor | license
            $table->string('title')->nullable();
            $table->date('due_date');
            $table->string('recurrence')->nullable(); // monthly | quarterly | annual | one_off
            $table->string('status')->default('open'); // open | done | overdue
            $table->date('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')
                ->references('id')->on('tenants')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->index(['company_id', 'due_date']);
            $table->index(['status', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_items');
    }
};
