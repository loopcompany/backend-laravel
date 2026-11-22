<?php
namespace App\Filament\Resources;
use App\Models\{LocalizationMessage,LocalizationLanguage};
use App\Services\LocalizationCatalog;
use App\Traits\HasFilamentPermissions;
use Filament\{Forms,Tables};
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
class LocalizationMessageResource extends Resource {
 use HasFilamentPermissions;
 protected static ?string $model=LocalizationMessage::class;
 protected static ?string $navigationGroup='زبان و ترجمه';protected static ?string $navigationLabel='ترجمه‌ها';protected static ?string $modelLabel='متن ترجمه';protected static ?string $pluralModelLabel='متن‌های ترجمه';protected static ?string $navigationIcon='heroicon-o-chat-bubble-left-ellipsis';
 protected static function getViewPermission():string{return 'view-translations';}
 protected static function getCreatePermission():string{return 'create-translations';}
 protected static function getEditPermission():string{return 'edit-translations';}
 protected static function getDeletePermission():string{return 'delete-translations';}
 public static function form(Form $form):Form {
  $fields=[Forms\Components\Select::make('scope')->label('برنامه')->options(LocalizationCatalog::SCOPES)->required(),Forms\Components\TextInput::make('message_key')->label('شناسه ثابت i18n')->helperText('شناسه باید با کد برنامه منطبق بماند؛ برای تغییر متن، فارسی یا ترجمه را ویرایش کنید.')->required()->disabledOn('edit')->maxLength(1500),Forms\Components\Textarea::make('source_fa')->label('متن معیار فارسی (قابل ویرایش)')->helperText('متغیرهای {{name}} و شناسه‌های جمع مانند _one و _other را حفظ کنید.')->required()->maxLength(20000)->columnSpanFull()];
  foreach(LocalizationLanguage::where('code','!=','fa')->orderBy('sort_order')->get() as $language)$fields[]=Forms\Components\Textarea::make('translations.'.$language->code)->label($language->native_name.' — '.$language->code)->maxLength(20000)->columnSpanFull();
  return $form->schema($fields);
 }
 public static function table(Table $table):Table{return $table->columns([
  Tables\Columns\TextColumn::make('scope')->label('برنامه')->formatStateUsing(fn($state)=>LocalizationCatalog::SCOPES[$state]??$state)->sortable(),
  Tables\Columns\TextColumn::make('message_key')->label('شناسه')->limit(45)->searchable(),
  Tables\Columns\TextColumn::make('source_fa')->label('متن فارسی')->limit(85)->searchable(),
  Tables\Columns\TextColumn::make('updated_at')->label('آخرین تغییر')->jalaliDateTime()->sortable(),
 ])->filters([Tables\Filters\SelectFilter::make('scope')->label('برنامه')->options(LocalizationCatalog::SCOPES)])->actions([Tables\Actions\EditAction::make(),Tables\Actions\DeleteAction::make()])->defaultSort('id','desc');}
 public static function getPages():array{return ['index'=>LocalizationMessageResource\Pages\ListLocalizationMessages::route('/'),'create'=>LocalizationMessageResource\Pages\CreateLocalizationMessage::route('/create'),'edit'=>LocalizationMessageResource\Pages\EditLocalizationMessage::route('/{record}/edit')];}
}
