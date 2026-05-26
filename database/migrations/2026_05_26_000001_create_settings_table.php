<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-tenant key/value settings table. Secrets (API keys etc.) are
 * stored encrypted via Laravel's Crypt facade — accessor on the model
 * handles encrypt/decrypt transparently.
 *
 * Reads fall back to `.env` config when no DB row exists, so existing
 * deploys keep working until an admin opens the Settings page.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->string('group');   // ai | embeddings | mail | billing | ...
            $table->string('key');     // anthropic_api_key | anthropic_model | ...
            $table->text('value')->nullable();
            $table->boolean('is_secret')->default(false);
            $table->timestamps();

            $table->foreign('tenant_id')
                ->references('id')->on('tenants')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->unique(['tenant_id', 'group', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
