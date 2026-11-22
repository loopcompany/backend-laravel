<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class LocalizationMessage extends Model {
 protected $guarded=[];protected $casts=['translations'=>'array'];
 protected static function booted():void {
  static::saving(function($row){$row->key_hash=hash('sha256',$row->message_key);if(static::where('scope',$row->scope)->where('key_hash',$row->key_hash)->when($row->exists,fn($q)=>$q->whereKeyNot($row->id))->exists())throw \Illuminate\Validation\ValidationException::withMessages(['message_key'=>'این شناسه برای برنامه انتخاب‌شده قبلاً ثبت شده است.']);app(\App\Services\LocalizationCatalog::class)->validateMessage($row->source_fa,$row->translations??[]);});
  static::saved(fn()=>app(\App\Services\LocalizationCatalog::class)->touch());
  static::deleted(fn()=>app(\App\Services\LocalizationCatalog::class)->touch());
 }
}
