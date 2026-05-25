<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            // The id IS the firm's slug (e.g. "samir") — matches subdomain.
            $table->string('id')->primary();
            $table->string('name');
            $table->string('plan')->default('free');
            $table->json('settings')->nullable();
            $table->json('branding')->nullable();
            $table->timestamps();
            // stancl/tenancy uses a `data` JSON column for any attribute not
            // present as a real column. We keep it for forward compatibility.
            $table->json('data')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
