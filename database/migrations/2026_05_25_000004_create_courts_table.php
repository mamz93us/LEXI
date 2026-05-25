<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courts', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->foreignId('court_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('courts')->nullOnDelete();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('governorate')->nullable();
            $table->unsignedInteger('sort_order')->default(100);
            $table->timestamps();

            $table->foreign('tenant_id')
                ->references('id')->on('tenants')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->index(['tenant_id', 'court_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courts');
    }
};
