<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
class LocalizationLanguage extends Model {
 protected $guarded=[];protected $casts=['is_enabled'=>'boolean'];
 protected static function booted():void {
  static::saving(function($row){
   if(!$row->exists){$parts=explode('-',trim((string)$row->code));$parts[0]=strtolower($parts[0]);foreach($parts as $index=>$part){if(!$index)continue;$parts[$index]=strlen($part)===4?ucfirst(strtolower($part)):(strlen($part)===2?strtoupper($part):strtolower($part));}$row->code=implode('-',$parts);}
   if(!preg_match('/^[a-z]{2,3}(?:-[a-zA-Z0-9]{2,8})*$/D',$row->code) || !in_array($row->direction,['rtl','ltr'],true))throw ValidationException::withMessages(['code'=>'شناسه استاندارد زبان و جهت نوشتار معتبر وارد کنید.']);

   if($row->exists && $row->isDirty('code')) throw ValidationException::withMessages(['code'=>'شناسه زبان پس از ایجاد قابل تغییر نیست.']);
   if($row->code==='fa' && (!$row->is_enabled || $row->direction!=='rtl')) throw ValidationException::withMessages(['is_enabled'=>'زبان معیار فارسی باید فعال و راست‌به‌چپ بماند.']);
  });
  static::deleting(function($row){if($row->code==='fa')throw ValidationException::withMessages(['code'=>'زبان معیار فارسی حذف نمی‌شود.']);});
  static::saved(fn()=>app(\App\Services\LocalizationCatalog::class)->touch());
  static::deleted(fn()=>app(\App\Services\LocalizationCatalog::class)->touch());
 }
}
