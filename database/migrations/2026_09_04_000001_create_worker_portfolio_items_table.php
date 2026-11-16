<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Worker portfolio / gallery items — Phase 34.
 *
 * Ownership follows the same convention as `worker_services` and
 * `worker_availability`: a `worker_id` FK straight to `users`, cascade-deleted
 * with the account. `WorkerProfile` reaches these through
 * hasMany(..., 'worker_id', 'user_id'), and since `worker_profiles.user_id` is
 * UNIQUE every item still belongs to exactly one profile.
 *
 * `image_path` is a relative path on the private `local` disk (like
 * `worker_profiles.photo`); there is no public URL. Upload handling and the
 * gallery UI are later steps — this migration only lays down the table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('worker_portfolio_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_id')->constrained('users')->cascadeOnDelete();
            $table->string('image_path');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('worker_portfolio_items');
    }
};
