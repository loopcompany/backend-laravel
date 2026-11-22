<?php
namespace App\Services;
use App\Models\{LocalizationLanguage,LocalizationMessage};
use Illuminate\Support\Facades\{Cache,DB,Log,Schema};
use Illuminate\Validation\ValidationException;
class LocalizationCatalog {
 private ?int $currentRevision=null;
 private array $loadedResources=[];
 private ?array $loadedLanguages=null;
 public const SCOPES=['customer'=>'اپ کاربر و ویندوز','technician'=>'اپ تکنسین','website'=>'وب‌سایت'];
 public function revision():int{return $this->currentRevision ??= (Schema::hasTable('localization_versions')?(int)DB::table('localization_versions')->where('id',1)->value('revision'):0);}
 public function touch():void {
  DB::table('localization_versions')->where('id',1)->increment('revision');
  $this->currentRevision=null;$this->loadedResources=[];$this->loadedLanguages=null;
  DB::afterCommit(function(){try{if(config('realtime.enabled'))event(new \App\Events\LocalizationChanged($this->revision()));}catch(\Throwable $e){Log::warning('Localization broadcast failed',['type'=>$e::class]);}});
 }
 public function languages():array {
  if($this->loadedLanguages!==null)return $this->loadedLanguages;
  if(!Schema::hasTable('localization_languages'))return [['code'=>'fa','name'=>'فارسی','native_name'=>'فارسی','direction'=>'rtl'],['code'=>'en','name'=>'انگلیسی','native_name'=>'English','direction'=>'ltr']];
  return $this->loadedLanguages=LocalizationLanguage::where('is_enabled',true)->orderBy('sort_order')->orderBy('id')->get(['code','name','native_name','direction'])->toArray();
 }
 public function resources(string $scope,string $locale):array {
  if(!Schema::hasTable('localization_messages'))return [];
  $revision=$this->revision();
  return $this->loadedResources[$scope.'.'.$locale] ??= Cache::remember("localization.{$revision}.{$scope}.{$locale}",3600,function()use($scope,$locale){
   $out=[];
   foreach(LocalizationMessage::where('scope',$scope)->get() as $row){$value=$locale==='fa'?$row->source_fa:($row->translations[$locale]??null);if(is_string($value)&&$value!=='')$out[$row->message_key]=$value;}
   return $out;
  });
 }
 public function validateMessage(string $source,array $translations):void {
  preg_match_all('/\{\{\s*(-?\s*[a-zA-Z_][a-zA-Z0-9_.]*)[^}]*\}\}/',$source,$matches);
  foreach($translations as $locale=>$message){
   if($message===null)continue;
   if(!is_string($message))throw ValidationException::withMessages(['translations'=>'مقدار ترجمه باید متن باشد؛ کلیدهای تو در تو هنگام ورود JSON باز می‌شوند.']);
   if($message==='')continue;
   foreach($matches[1] as $variable)if(!preg_match('/\{\{\s*'.preg_quote($variable,'/').'\s*(?:,[^}]*)?\}\}/',$message))throw ValidationException::withMessages(['translations.'.$locale=>'متغیر {{'.$variable.'}} باید در ترجمه حفظ شود.']);
  }
 }
 public function import(string $scope,string $locale,array $resources):int {
  if(!isset(self::SCOPES[$scope]) || !LocalizationLanguage::where('code',$locale)->exists())throw ValidationException::withMessages(['resources'=>'برنامه یا زبان معتبر نیست.']);
  $flat=[];
  $flatten=function(array $data,string $prefix='')use(&$flatten,&$flat){foreach($data as $key=>$value){$key=$prefix.(string)$key;if(is_array($value))$flatten($value,$key.'.');elseif(is_string($value))$flat[$key]=$value;else throw ValidationException::withMessages(['resources'=>'مقادیر JSON باید متن باشند.']);}};
  $flatten($resources);
  if(count($flat)>15000)throw ValidationException::withMessages(['resources'=>'بیش از ۱۵۰۰۰ کلید در یک نوبت مجاز نیست.']);
  return DB::transaction(function()use($scope,$locale,$flat){
   foreach($flat as $key=>$value){
    if($key===''||mb_strlen($key)>1500||mb_strlen($value)>20000)throw ValidationException::withMessages(['resources'=>'طول شناسه یا متن بیش از حد مجاز است.']);
    $row=LocalizationMessage::firstOrNew(['scope'=>$scope,'key_hash'=>hash('sha256',$key)]);
    $row->message_key=$key;
    if($locale==='fa')$row->source_fa=$value;
    else {if(!$row->exists)throw ValidationException::withMessages(['resources'=>'ابتدا متن فارسی کلید '.$key.' تعریف شود.']);$translations=$row->translations??[];$translations[$locale]=$value;$row->translations=$translations;}
    // Query builder avoids emitting thousands of WebSocket events; one version update follows.
    $this->validateMessage($row->source_fa,$row->translations??[]);
    DB::table('localization_messages')->updateOrInsert(['scope'=>$scope,'key_hash'=>$row->key_hash],['message_key'=>$key,'source_fa'=>$row->source_fa,'translations'=>json_encode($row->translations??[],JSON_UNESCAPED_UNICODE),'created_at'=>$row->created_at??now(),'updated_at'=>now()]);
   }
   $this->touch();return count($flat);
  });
 }
 public function siteText(string $source):string {
  $locale=app()->getLocale();$values=$this->resources('website',$locale);$base=$locale==='fa'?$values:$this->resources('website','fa');
  return $values[$source]??$base[$source]??$source;
 }
}
