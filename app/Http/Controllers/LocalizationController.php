<?php
namespace App\Http\Controllers;
use App\Services\LocalizationCatalog;
use Illuminate\Http\Request;
class LocalizationController extends Controller {
 public function bootstrap(Request $request,LocalizationCatalog $catalog){
  $data=$request->validate(['app'=>['required',\Illuminate\Validation\Rule::in(array_keys(LocalizationCatalog::SCOPES))],'locale'=>['nullable','string','max:35']]);
  $languages=$catalog->languages();$codes=array_column($languages,'code');$locale=in_array($data['locale']??'fa',$codes,true)?($data['locale']??'fa'):'fa';
  $resources=[];foreach(array_unique(['fa',$locale]) as $lng)$resources[$lng]=['translation'=>(object)$catalog->resources($data['app'],$lng)];
  return response()->json(['revision'=>$catalog->revision(),'base_locale'=>'fa','locale'=>$locale,'languages'=>$languages,'resources'=>$resources,'realtime'=>[
   'enabled'=>(bool)config('realtime.enabled'),'key'=>config('broadcasting.connections.reverb.key'),'host'=>config('realtime.host'),'port'=>config('realtime.port'),'scheme'=>config('realtime.scheme'),'channels'=>['localization'],
  ]])->header('Cache-Control','no-cache');
 }
}
