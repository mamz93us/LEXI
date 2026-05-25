<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_requests', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->foreignId('hearing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();
            $table->foreignId('request_type_id')->constrained()->cascadeOnDelete();
            $table->string('requesting_party')->nullable(); // claimant | defendant | other
            $table->string('status')->default('pending');   // pending | granted | rejected | deferred
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')
                ->references('id')->on('tenants')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->index(['case_id', 'hearing_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_requests');
    }
};
