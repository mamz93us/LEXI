<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('judgments', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();
            $table->foreignId('judgment_type_id')->constrained()->cascadeOnDelete();
            $table->date('judgment_date');
            $table->string('presence_type')->default('in_presence'); // in_presence | in_absentia
            $table->text('summary')->nullable();
            $table->date('appeal_deadline')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')
                ->references('id')->on('tenants')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->index(['case_id', 'judgment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('judgments');
    }
};
