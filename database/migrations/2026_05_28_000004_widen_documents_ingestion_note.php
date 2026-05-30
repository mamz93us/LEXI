<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `ingestion_note` was varchar(255) but holds extraction error messages
 * (which can be longer). Writing a too-long note threw its OWN truncation
 * error inside the failure handler, masking the real error and leaving
 * the row stuck on `ingesting`. Widen to text.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->text('ingestion_note')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('ingestion_note')->nullable()->change();
        });
    }
};
