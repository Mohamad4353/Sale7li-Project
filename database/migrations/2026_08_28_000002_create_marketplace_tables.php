<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('service_categories', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('name_ar'); $table->string('slug')->unique();
            $table->text('description')->nullable(); $table->text('description_ar')->nullable(); $table->string('icon', 80)->nullable();
            $table->decimal('base_price', 10, 2)->default(0); $table->boolean('is_active')->default(true)->index(); $table->timestamps();
        });

        Schema::create('worker_profiles', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('bio')->nullable(); $table->text('bio_ar')->nullable(); $table->string('photo')->nullable();
            $table->unsignedSmallInteger('experience_years')->default(0); $table->enum('verification_status', ['pending','verified','rejected','suspended'])->default('pending')->index();
            $table->decimal('rating_average', 3, 2)->default(0); $table->unsignedInteger('rating_count')->default(0); $table->unsignedInteger('completed_jobs')->default(0);
            $table->decimal('completion_rate', 5, 2)->default(100); $table->decimal('cancellation_rate', 5, 2)->default(0); $table->decimal('service_radius_km', 8, 2)->default(10); $table->timestamps();
        });

        Schema::create('worker_services', function (Blueprint $table) {
            $table->id(); $table->foreignId('worker_id')->constrained('users')->cascadeOnDelete(); $table->foreignId('service_category_id')->constrained()->cascadeOnDelete();
            $table->decimal('price_from', 10, 2)->nullable(); $table->boolean('is_active')->default(true); $table->unique(['worker_id','service_category_id']); $table->timestamps();
        });

        Schema::create('worker_availability', function (Blueprint $table) {
            $table->id(); $table->foreignId('worker_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week'); $table->time('start_time')->nullable(); $table->time('end_time')->nullable(); $table->boolean('is_available')->default(true);
            $table->unique(['worker_id','day_of_week']); $table->timestamps();
        });

        Schema::create('addresses', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->string('title'); $table->string('street');
            $table->string('city'); $table->string('area')->nullable(); $table->string('postal_code')->nullable(); $table->text('instructions')->nullable(); $table->boolean('is_default')->default(false); $table->timestamps();
        });

        Schema::create('service_requests', function (Blueprint $table) {
            $table->id(); $table->string('reference')->unique(); $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('worker_id')->nullable()->constrained('users')->nullOnDelete(); $table->foreignId('service_category_id')->constrained()->restrictOnDelete();
            $table->foreignId('address_id')->nullable()->constrained()->nullOnDelete(); $table->text('problem_description'); $table->json('problem_images')->nullable();
            $table->date('preferred_date')->nullable(); $table->time('preferred_time')->nullable(); $table->boolean('is_emergency')->default(false)->index();
            $table->enum('status', ['pending','accepted','scheduled','on_the_way','in_progress','completed','cancelled','rejected'])->default('pending')->index();
            $table->decimal('quoted_total',10,2)->nullable(); $table->decimal('final_total',10,2)->nullable(); $table->enum('payment_status',['pending','paid','failed','refunded','partially_refunded'])->default('pending');
            $table->timestamp('completed_at')->nullable(); $table->timestamps();
        });

        Schema::create('appointments', function (Blueprint $table) {
            $table->id(); $table->foreignId('service_request_id')->unique()->constrained()->cascadeOnDelete(); $table->foreignId('worker_id')->constrained('users')->cascadeOnDelete();
            $table->date('scheduled_date'); $table->time('scheduled_time'); $table->enum('status',['scheduled','rescheduled','cancelled','completed'])->default('scheduled'); $table->timestamps();
            $table->index(['worker_id','scheduled_date','scheduled_time']);
        });

        Schema::create('quotations', function (Blueprint $table) {
            $table->id(); $table->foreignId('service_request_id')->constrained()->cascadeOnDelete(); $table->foreignId('worker_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('base_price',10,2)->default(0); $table->decimal('additional_charges',10,2)->default(0); $table->decimal('discount',10,2)->default(0); $table->decimal('tax',10,2)->default(0); $table->decimal('total',10,2)->default(0);
            $table->text('notes')->nullable(); $table->enum('status',['pending','accepted','rejected','expired'])->default('pending'); $table->timestamp('responded_at')->nullable(); $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id(); $table->foreignId('service_request_id')->constrained()->cascadeOnDelete(); $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->string('transaction_reference')->unique(); $table->decimal('amount',10,2); $table->string('currency',3)->default('USD');
            $table->enum('status',['pending','paid','failed','refunded','partially_refunded'])->default('pending'); $table->timestamp('paid_at')->nullable(); $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id(); $table->string('invoice_number')->unique(); $table->foreignId('service_request_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete(); $table->foreignId('worker_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('line_items'); $table->decimal('subtotal',10,2); $table->decimal('discount',10,2)->default(0); $table->decimal('tax',10,2)->default(0); $table->decimal('total',10,2); $table->enum('payment_status',['pending','paid','refunded','partially_refunded'])->default('pending'); $table->timestamps();
        });
    }
    public function down(): void { foreach (['invoices','payments','quotations','appointments','service_requests','addresses','worker_availability','worker_services','worker_profiles','service_categories'] as $t) Schema::dropIfExists($t); }
};
