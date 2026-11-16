<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up():void {Schema::table('service_requests',function(Blueprint $t){$t->text('completion_notes')->nullable()->after('final_total');$t->json('completion_images')->nullable()->after('completion_notes');});} public function down():void {Schema::table('service_requests',function(Blueprint $t){$t->dropColumn(['completion_notes','completion_images']);});}};
