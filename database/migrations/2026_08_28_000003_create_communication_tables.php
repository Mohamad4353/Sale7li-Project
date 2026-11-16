<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('favorites', fn(Blueprint $t)=>[$t->id(),$t->foreignId('customer_id')->constrained('users')->cascadeOnDelete(),$t->foreignId('worker_id')->constrained('users')->cascadeOnDelete(),$t->timestamps(),$t->unique(['customer_id','worker_id'])]);
  Schema::create('conversations', function(Blueprint $t){$t->id();$t->foreignId('service_request_id')->constrained()->cascadeOnDelete();$t->foreignId('customer_id')->constrained('users')->cascadeOnDelete();$t->foreignId('worker_id')->constrained('users')->cascadeOnDelete();$t->timestamps();$t->unique('service_request_id');});
  Schema::create('messages', function(Blueprint $t){$t->id();$t->foreignId('conversation_id')->constrained()->cascadeOnDelete();$t->foreignId('sender_id')->constrained('users')->cascadeOnDelete();$t->text('body');$t->json('attachments')->nullable();$t->timestamp('read_at')->nullable();$t->timestamps();$t->index(['conversation_id','created_at']);});
  Schema::create('notifications', function(Blueprint $t){$t->id();$t->foreignId('user_id')->constrained()->cascadeOnDelete();$t->string('type');$t->string('title');$t->text('message');$t->foreignId('service_request_id')->nullable()->constrained()->nullOnDelete();$t->timestamp('read_at')->nullable();$t->timestamps();$t->index(['user_id','read_at']);});
  Schema::create('reviews', function(Blueprint $t){$t->id();$t->foreignId('service_request_id')->unique()->constrained()->cascadeOnDelete();$t->foreignId('customer_id')->constrained('users')->cascadeOnDelete();$t->foreignId('worker_id')->constrained('users')->cascadeOnDelete();$t->unsignedTinyInteger('rating');$t->text('comment')->nullable();$t->json('photos')->nullable();$t->timestamps();$t->index(['worker_id','rating']);});
  Schema::create('complaints', function(Blueprint $t){$t->id();$t->foreignId('service_request_id')->nullable()->constrained()->nullOnDelete();$t->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();$t->string('category');$t->enum('priority',['low','medium','high','urgent'])->default('medium');$t->enum('status',['open','investigating','resolved','closed'])->default('open');$t->text('description');$t->text('internal_notes')->nullable();$t->timestamps();$t->index(['status','priority']);});
  Schema::create('announcements', function(Blueprint $t){$t->id();$t->enum('audience',['all','customers','workers']);$t->string('title');$t->text('message');$t->enum('priority',['normal','important','urgent'])->default('normal');$t->dateTime('starts_at')->nullable();$t->dateTime('ends_at')->nullable();$t->boolean('is_active')->default(true);$t->timestamps();});
  Schema::create('activity_logs', function(Blueprint $t){$t->id();$t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();$t->string('action');$t->string('subject_type')->nullable();$t->unsignedBigInteger('subject_id')->nullable();$t->json('metadata')->nullable();$t->ipAddress('ip_address')->nullable();$t->timestamps();$t->index(['subject_type','subject_id']);});
 }
 public function down(): void { foreach(['activity_logs','announcements','complaints','reviews','notifications','messages','conversations','favorites'] as $t) Schema::dropIfExists($t); }
};
