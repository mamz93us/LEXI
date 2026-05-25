<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ip_assets', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('asset_type'); // trademark | patent | copyright
            $table->string('title');
            $table->string('classification')->nullable(); // Nice class number / IPC code
            $table->string('office_serial')->nullable();
            $table->date('filed_on')->nullable();
            $table->date('granted_on')->nullable();
            $table->date('renewal_date')->nullable();
            $table->string('status')->default('active'); // pending | active | expired | abandoned
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')
                ->references('id')->on('tenants')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->index(['renewal_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ip_assets');
    }
};
