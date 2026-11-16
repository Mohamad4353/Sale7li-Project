<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WHISH manual payment support — additive only, safe on the populated database.
 *
 * Every column is new and nullable (or defaulted). No existing column is
 * altered, no data is touched. The `status` enum already carries
 * pending / paid / failed, so it is left untouched: a WHISH payment is
 * `pending` while awaiting review, `paid` once an admin approves it, and
 * `failed` if rejected. Existing rows are back-filled to payment_method
 * 'card' so the historical instant-capture payments stay labelled.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('payment_method', 20)->default('card')->after('transaction_reference');
            $table->string('whish_reference', 64)->nullable()->after('payment_method');
            $table->string('payment_proof_path')->nullable()->after('whish_reference');
            $table->timestamp('submitted_at')->nullable()->after('paid_at');
            $table->timestamp('verified_at')->nullable()->after('submitted_at');
            $table->foreignId('verified_by')->nullable()->after('verified_at')->constrained('users')->nullOnDelete();
            $table->string('rejection_reason', 500)->nullable()->after('verified_by');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->dropColumn([
                'payment_method',
                'whish_reference',
                'payment_proof_path',
                'submitted_at',
                'verified_at',
                'verified_by',
                'rejection_reason',
            ]);
        });
    }
};
