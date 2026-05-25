<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->string('legal_form'); // llc | jsc | sole | branch
            $table->string('commercial_register_no')->nullable();
            $table->string('tax_card_no')->nullable();
            $table->string('gafi_file_no')->nullable();
            $table->bigInteger('capital_piastres')->nullable();
            $table->json('activity_codes')->nullable();
            $table->string('status')->default('active'); // active | suspended | dissolved | in_formation
            $table->string('formation_stage')->nullable(); // free-text or coded stage
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')
                ->references('id')->on('tenants')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
