<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 12 P10-10 — structured, localisable notifications.
 *
 * The table stores only pre-rendered `title` / `message` prose, so bilingual
 * notification text is impossible without freezing a locale at write time. This
 * adds a nullable JSON `data` column for the structured parameters (request
 * reference, status, amount, …). The human-readable text is then rendered from
 * the notifications translation files at display time.
 *
 * Additive + nullable + reversible. Legacy rows keep `data = NULL` and continue
 * to render from their stored `title` / `message`. No data backfill.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->json('data')->nullable()->after('message');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn('data');
        });
    }
};
