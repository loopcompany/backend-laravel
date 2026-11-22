<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  Schema::create('registration_code_format_changes',function(Blueprint $t){$t->id();$t->string('table_name');$t->unsignedBigInteger('record_id');$t->string('field_name');$t->string('old_value');$t->string('new_value');});
  DB::transaction(function(){
   foreach(['users'=>'code','employment_files'=>'personnel_code','technicians'=>'referral_code','organizations'=>'organization_code'] as $table=>$field) {
    DB::table($table)->whereNotNull($field)->orderBy('id')->chunkById(100,function($rows)use($table,$field){
     foreach($rows as $row) {
      $old=$row->$field;
      if(!\App\Services\RegistrationCodeService::isIssued($old))continue;
      $new=str_replace(['-','.'],'',$old);if($old===$new)continue;
      if(DB::table($table)->where($field,$new)->where('id','!=',$row->id)->exists())throw new \RuntimeException('Numeric registration code collision; migration aborted');
      DB::table('registration_code_format_changes')->insert(['table_name'=>$table,'record_id'=>$row->id,'field_name'=>$field,'old_value'=>$old,'new_value'=>$new]);
      DB::table($table)->where('id',$row->id)->update([$field=>$new]);
      if($table==='technicians'){foreach(DB::table('technicians')->where('other_referral_code',$old)->get(['id']) as $referrer){DB::table('registration_code_format_changes')->insert(['table_name'=>'technicians','record_id'=>$referrer->id,'field_name'=>'other_referral_code','old_value'=>$old,'new_value'=>$new]);DB::table('technicians')->where('id',$referrer->id)->update(['other_referral_code'=>$new]);}}
     }
    });
   }
  });
 }
 public function down():void {
  DB::transaction(function(){foreach(DB::table('registration_code_format_changes')->orderByDesc('id')->get() as $row)DB::table($row->table_name)->where('id',$row->record_id)->where($row->field_name,$row->new_value)->update([$row->field_name=>$row->old_value]);});
  Schema::dropIfExists('registration_code_format_changes');
 }
};
