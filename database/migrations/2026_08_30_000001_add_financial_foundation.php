<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 7 Step 1 — financial foundation (additive, no behaviour change).
 *
 * - platform_settings: a small key/value store; seeded with the single source of
 *   truth for the active commission rate (0.1000 = 10%).
 * - payments: worker link + frozen-at-capture commission columns (all nullable —
 *   existing/mock rows carry no commission data and none is fabricated), a real
 *   UNIQUE(service_request_id) to back the existing updateOrCreate convention,
 *   and indexes for the upcoming earnings/admin reads.
 * - service_requests: (worker_id, status) index — also speeds the Phase 6
 *   worker dashboard aggregates.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('value');
            $table->timestamps();
        });

        DB::table('platform_settings')->insert([
            'key' => 'commission_rate',
            'value' => '0.1000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('worker_id')->nullable()->after('customer_id')
                ->constrained('users')->nullOnDelete();
            $table->decimal('commission_rate', 5, 4)->nullable()->after('amount');
            $table->decimal('commission_amount', 10, 2)->nullable()->after('commission_rate');
            $table->decimal('worker_net', 10, 2)->nullable()->after('commission_amount');

            $table->unique('service_request_id');
            $table->index('status');
            $table->index(['worker_id', 'status']);
        });

        Schema::table('service_requests', function (Blueprint $table) {
            $table->index(['worker_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropIndex(['worker_id', 'status']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['worker_id', 'status']);
            $table->dropIndex(['status']);
            $table->dropUnique(['service_request_id']);
            $table->dropConstrainedForeignId('worker_id');
            $table->dropColumn(['commission_rate', 'commission_amount', 'worker_net']);
        });

        Schema::dropIfExists('platform_settings');
    }
};
