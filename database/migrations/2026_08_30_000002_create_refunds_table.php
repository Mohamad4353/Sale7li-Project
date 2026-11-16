<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 7 Step 4 — internal refund ledger (additive, no behaviour change).
 *
 * A refund is a NEW financial fact, never a mutation of the captured payment.
 * The `payments` / `invoices` rows are left exactly as frozen at capture; a
 * fully-refunded payment keeps status `paid` and its original amounts. The
 * refund record alone carries the reversal.
 *
 * There is no real payment gateway: `status = completed` means a confirmed
 * application-level refund state, not an external PSP transaction. No gateway
 * identifiers are stored.
 *
 * UNIQUE(payment_id, type) is the database backstop for "one full refund per
 * payment" — the application checks first (under a row lock) and this constraint
 * catches any concurrent race that slips past the check.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->enum('type', ['full'])->default('full');
            $table->enum('status', ['pending', 'completed', 'failed'])->default('completed');
            $table->text('reason')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['payment_id', 'type']);
            $table->index(['payment_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
