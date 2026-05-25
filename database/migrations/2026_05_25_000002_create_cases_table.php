<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Minimal `cases` schema for Milestone 1.1. The Egyptian litigation core
 * (degree, court_id, case_type_id, circuit, roll_no, parent_case_id) is
 * added in Milestone 1.2 — see CLAUDE.md §4A.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cases', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('case_number');
            $table->string('title');
            $table->string('status')->default('open'); // open|on_hold|closed
            $table->bigInteger('dispute_value_piastres')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')
                ->references('id')->on('tenants')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->unique(['tenant_id', 'case_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cases');
    }
};
