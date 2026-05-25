<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_entries', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Polymorphic: case / company / client / matter
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->date('worked_on');
            $table->unsignedInteger('minutes');
            $table->bigInteger('rate_piastres')->nullable();
            $table->text('description')->nullable();
            $table->boolean('billable')->default(true);
            $table->boolean('invoiced')->default(false);
            $table->timestamps();

            $table->foreign('tenant_id')
                ->references('id')->on('tenants')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->index(['subject_type', 'subject_id']);
            $table->index(['user_id', 'worked_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_entries');
    }
};
