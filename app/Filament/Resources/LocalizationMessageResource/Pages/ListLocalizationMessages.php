<?php
namespace App\Filament\Resources\LocalizationMessageResource\Pages;
use App\Filament\Resources\LocalizationMessageResource;
use App\Services\LocalizationCatalog;
use App\Models\LocalizationLanguage;
use Filament\{Actions,Forms};
use Filament\Notifications\Notification;
class ListLocalizationMessages extends \Filament\Resources\Pages\ListRecords {
 protected static string $resource=LocalizationMessageResource::class;
 protected function getHeaderActions():array {
  $scope=fn()=>Forms\Components\Select::make('scope')->label('برنامه')->options(LocalizationCatalog::SCOPES)->required();
  $locale=fn()=>Forms\Components\Select::make('locale')->label('زبان')->options(LocalizationLanguage::pluck('native_name','code'))->required();
  return [Actions\CreateAction::make(),
   Actions\Action::make('import_json')->label('ورود JSON استاندارد i18n')->visible(fn()=>LocalizationMessageResource::canCreate() && auth('admin')->user()?->can('edit-translations'))->form([$scope(),$locale(),Forms\Components\Textarea::make('resources')->label('محتوای فایل JSON')->rules(['json'])->required()->maxLength(2000000)->rows(14)])->action(function(array $data){abort_unless(LocalizationMessageResource::canCreate() && auth('admin')->user()?->can('edit-translations'),403);$resources=json_decode($data['resources'],true,512,JSON_THROW_ON_ERROR);if(!is_array($resources) || ($resources!==[] && array_is_list($resources)))throw \Illuminate\Validation\ValidationException::withMessages(['resources'=>'JSON باید یک شیء شامل شناسه و متن ترجمه باشد.']);$n=app(LocalizationCatalog::class)->import($data['scope'],$data['locale'],$resources);Notification::make()->success()->title("{$n} ترجمه ذخیره شد")->send();}),
   Actions\Action::make('export_json')->label('خروجی JSON')->form([$scope(),$locale()])->action(function(array $data){$json=json_encode((object)app(LocalizationCatalog::class)->resources($data['scope'],$data['locale']),JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);return response()->streamDownload(fn()=>print($json),$data['scope'].'.'.$data['locale'].'.json',['Content-Type'=>'application/json; charset=utf-8']);}),
  ];
 }
}
