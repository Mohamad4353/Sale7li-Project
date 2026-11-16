<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Customer-initiated "please move my appointment" proposals.
 *
 * Deliberately NOT a new workflow/state machine: `ServiceRequest.status` is
 * never touched by this table. `service_request_id` is intentionally NOT
 * unique — a request can accumulate a history of proposals over time (one
 * declined, another submitted later, etc.), exactly like `quotations`.
 *
 * Only one row may be `pending` per request at a time; that is an
 * application-level invariant (enforced in the controller, mirroring the
 * quotation-lock pattern), not a DB constraint, since every other status is
 * expected to coexist historically.
 *
 * `superseded` covers the case where the assigned worker changes the
 * appointment through their own existing scheduling control while a customer
 * proposal is still pending — the proposal is no longer actionable, but the
 * row (and the customer's original reason) is preserved rather than deleted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reschedule_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_request_id')->constrained()->cascadeOnDelete();
            $table->date('proposed_date');
            $table->time('proposed_time');
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'accepted', 'declined', 'superseded'])->default('pending')->index();
            $table->text('decline_reason')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reschedule_requests');
    }
};
