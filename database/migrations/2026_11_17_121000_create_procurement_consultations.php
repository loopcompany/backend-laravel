<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void { Schema::create('procurement_consultations',function(Blueprint $t) {
  $t->id(); $t->foreignId('user_id')->constrained()->cascadeOnDelete(); $t->uuid('client_request_id');
  $t->string('item_id',40); $t->string('title',100); $t->unsignedInteger('new_count')->default(0); $t->unsignedInteger('used_count')->default(0);
  $t->unsignedBigInteger('budget_rial'); $t->text('description'); $t->text('address'); $t->string('status',30)->default('pending'); $t->text('admin_notes')->nullable();
  $t->timestamps(); $t->unique(['user_id','client_request_id']);
 }); }
 public function down():void { Schema::dropIfExists('procurement_consultations'); }
};
