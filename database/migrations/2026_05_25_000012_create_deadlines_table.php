<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deadlines', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            // Polymorphic owner: case / judgment / hearing / company / etc.
            $table->string('deadline_for_type');
            $table->unsignedBigInteger('deadline_for_id');
            $table->string('type'); // appeal_window | cassation_window | filing | renewal | ...
            $table->date('due_date');
            $table->json('alert_offsets_days')->nullable(); // [-14, -7, -3, -1, 0]
            $table->string('status')->default('open'); // open | met | missed | cancelled
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')
                ->references('id')->on('tenants')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->index(['deadline_for_type', 'deadline_for_id']);
            $table->index(['due_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deadlines');
    }
};
