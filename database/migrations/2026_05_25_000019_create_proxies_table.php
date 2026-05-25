<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proxies', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete(); // principal
            $table->string('type')->default('specific'); // general | specific
            $table->string('notary_serial')->nullable();
            $table->date('issue_date');
            $table->date('expiry_date')->nullable();
            $table->text('scope')->nullable();
            $table->string('status')->default('valid'); // valid | expiring | expired | revoked
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')
                ->references('id')->on('tenants')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });

        Schema::create('proxy_user', function (Blueprint $table) {
            $table->foreignId('proxy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['proxy_id', 'user_id']);
        });

        Schema::create('proxy_case', function (Blueprint $table) {
            $table->foreignId('proxy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();
            $table->primary(['proxy_id', 'case_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proxy_case');
        Schema::dropIfExists('proxy_user');
        Schema::dropIfExists('proxies');
    }
};
