<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::table('users', function(Blueprint $t) {
  $t->timestamp('profile_completed_at')->nullable();
  $t->unsignedBigInteger('profile_completion_token_id')->nullable();
  foreach (['home_number','home_unit','home_floor','work_number','work_unit','work_floor'] as $name) $t->string($name,30)->nullable();
 }); }
 public function down(): void { Schema::table('users', fn(Blueprint $t)=>$t->dropColumn(['profile_completed_at','profile_completion_token_id','home_number','home_unit','home_floor','work_number','work_unit','work_floor'])); }
};
