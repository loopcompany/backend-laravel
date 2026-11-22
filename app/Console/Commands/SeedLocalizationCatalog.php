<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
class SeedLocalizationCatalog extends Command {
 protected $signature='localization:seed';protected $description='Register missing shipped i18n keys while preserving all admin edits';
 public function handle():int {
  $file=resource_path('localization/seed.json');$rows=json_decode(file_get_contents($file),true,512,JSON_THROW_ON_ERROR);$count=0;
  DB::transaction(function()use($rows,&$count){foreach($rows as $row){$translations=$row['translations_data']??[];unset($row['translations_data']);$count+=DB::table('localization_messages')->insertOrIgnore($row+['key_hash'=>hash('sha256',$row['message_key']),'translations'=>json_encode($translations,JSON_UNESCAPED_UNICODE),'created_at'=>now(),'updated_at'=>now()]);}if($count)app(\App\Services\LocalizationCatalog::class)->touch();});
  $this->info("Registered {$count} missing messages; existing translations preserved.");return self::SUCCESS;
 }
}
