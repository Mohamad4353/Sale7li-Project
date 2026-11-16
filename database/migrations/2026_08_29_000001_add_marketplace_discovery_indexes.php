<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes for the marketplace discovery queries added in Phase 5.
 *
 * - worker_services.service_category_id: worker discovery filters professionals
 *   by category (`whereHas('services', fn => where('service_category_id', X))`).
 *   The existing UNIQUE(worker_id, service_category_id) covers a worker_id
 *   prefix but not service_category_id on its own.
 *
 * - worker_profiles(verification_status, rating_average): discovery always
 *   restricts to verified profiles and then orders by rating for the
 *   "Highest rated" sort; a composite index serves both the filter and the
 *   sort in one pass.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('worker_services', function (Blueprint $table) {
            $table->index('service_category_id', 'worker_services_category_idx');
        });

        Schema::table('worker_profiles', function (Blueprint $table) {
            $table->index(['verification_status', 'rating_average'], 'worker_profiles_verified_rating_idx');
        });
    }

    public function down(): void
    {
        Schema::table('worker_services', function (Blueprint $table) {
            $table->dropIndex('worker_services_category_idx');
        });

        Schema::table('worker_profiles', function (Blueprint $table) {
            $table->dropIndex('worker_profiles_verified_rating_idx');
        });
    }
};
