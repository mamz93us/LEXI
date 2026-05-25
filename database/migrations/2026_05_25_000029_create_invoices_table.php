<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number');
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->string('status')->default('draft'); // draft | sent | partly_paid | paid | void
            $table->string('currency', 8)->default('EGP');
            $table->bigInteger('subtotal_piastres')->default(0);
            $table->bigInteger('tax_piastres')->default(0);
            $table->bigInteger('total_piastres')->default(0);
            $table->bigInteger('paid_piastres')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')
                ->references('id')->on('tenants')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->unique(['tenant_id', 'number']);
            $table->index(['client_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
