<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->string('title');
            $table->string('type')->default('contract'); // contract|poa|memo|filing|other
            $table->string('owner_type')->nullable();    // polymorphic owner
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->unsignedBigInteger('current_version_id')->nullable()->index();
            $table->string('format')->default('docx');   // docx|pdf|other
            $table->longText('ocr_text')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')
                ->references('id')->on('tenants')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->index(['owner_type', 'owner_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
