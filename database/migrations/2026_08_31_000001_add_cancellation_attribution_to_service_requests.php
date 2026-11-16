<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 11 Item 5 — fair cancellation attribution (additive, nullable).
 *
 * `cancelled_by` records who ended the request at the moment it was cancelled
 * ('customer' | 'worker' | 'admin'); it is never inferred later. Existing rows
 * keep NULL and the reputation calculation treats NULL as "counts" so historical
 * behaviour is unchanged. `cancellation_reason` stores an allow-listed reason
 * code, rendered through the localisation files.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->string('cancelled_by', 20)->nullable()->after('completed_at');
            $table->string('cancellation_reason', 40)->nullable()->after('cancelled_by');
        });
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropColumn(['cancelled_by', 'cancellation_reason']);
        });
    }
};
