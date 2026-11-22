<?php
namespace App\Support;
use Filament\Forms\Components\DatePicker;
class JalaliDateField {
 public static function make(string $name):DatePicker {
  return DatePicker::make($name)->jalali()->native(false)->afterStateHydrated(function(DatePicker $component,$state) {
   if(is_string($state) && preg_match('/^1[34][0-9]{2}[\/-]/',$state)) {
    try {$component->state(\Morilog\Jalali\Jalalian::fromFormat('Y/m/d',str_replace('-','/',$state))->toCarbon()->format('Y-m-d'));} catch(\Throwable) {$component->state(null);}
   }
  });
 }
}
