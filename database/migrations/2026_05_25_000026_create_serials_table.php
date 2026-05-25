<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Government paperwork tracker. Polymorphically linked to a case or
 * company so the firm can answer "what serials are pending for client X?"
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('serials', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->string('serial_no');
            $table->string('document_name');
            $table->string('issuing_authority')->nullable();
            $table->string('owner_type')->nullable(); // App\Models\LegalCase | App\Models\Company
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->bigInteger('fees_piastres')->nullable();
            $table->date('issued_at')->nullable();
            $table->string('status')->default('pending'); // pending | issued | collected
            $table->string('attachment_ref')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')
                ->references('id')->on('tenants')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->index(['owner_type', 'owner_id']);
            $table->unique(['tenant_id', 'serial_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('serials');
    }
};
