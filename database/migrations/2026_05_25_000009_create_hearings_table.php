<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hearings', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();
            $table->foreignId('court_id')->nullable()->constrained()->nullOnDelete();
            $table->date('session_date');
            $table->string('purpose')->nullable();
            $table->string('attended_by')->nullable();
            $table->string('outcome')->nullable();
            $table->string('postponement_reason')->nullable();
            $table->date('next_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')
                ->references('id')->on('tenants')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->index(['case_id', 'session_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hearings');
    }
};
