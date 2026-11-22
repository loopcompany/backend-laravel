<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};
return new class extends Migration {
 public function up():void {
  Schema::create('localization_languages',function(Blueprint $t){$t->id();$t->string('code',35)->unique();$t->string('name',100);$t->string('native_name',100);$t->string('direction',3)->default('ltr');$t->boolean('is_enabled')->default(true);$t->unsignedInteger('sort_order')->default(0);$t->timestamps();});
  Schema::create('localization_messages',function(Blueprint $t){$t->id();$t->string('scope',30);$t->text('message_key');$t->char('key_hash',64);$t->text('source_fa');$t->json('translations')->nullable();$t->timestamps();$t->unique(['scope','key_hash']);});
  Schema::create('localization_versions',function(Blueprint $t){$t->unsignedInteger('id')->primary();$t->unsignedBigInteger('revision')->default(1);});
  DB::table('localization_versions')->insert(['id'=>1,'revision'=>1]);
  foreach([['fa','فارسی','فارسی','rtl',0],['en','انگلیسی','English','ltr',1]] as [$code,$name,$native,$direction,$sort]) DB::table('localization_languages')->insert(['code'=>$code,'name'=>$name,'native_name'=>$native,'direction'=>$direction,'sort_order'=>$sort,'is_enabled'=>true,'created_at'=>now(),'updated_at'=>now()]);
 }
 public function down():void {Schema::dropIfExists('localization_messages');Schema::dropIfExists('localization_languages');Schema::dropIfExists('localization_versions');}
};
