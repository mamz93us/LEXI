<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Each AI draft is now versioned: every refinement (AI- or human-driven)
 * creates a new ai_generations row pointing at the previous via parent_id.
 * Approval applies to a single revision; the chain is preserved for audit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_generations', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id')->nullable()->after('subject_id');
            $table->text('user_instruction')->nullable()->after('prompt');
            $table->string('revision_kind')->default('initial')->after('user_instruction');
            // initial | ai_refine | manual_edit

            $table->foreign('parent_id')
                ->references('id')->on('ai_generations')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('ai_generations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
            $table->dropColumn(['user_instruction', 'revision_kind']);
        });
    }
};
