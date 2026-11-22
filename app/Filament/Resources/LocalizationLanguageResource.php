<?php
namespace App\Filament\Resources;
use App\Models\LocalizationLanguage;
use App\Traits\HasFilamentPermissions;
use Filament\{Forms,Tables};
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
class LocalizationLanguageResource extends Resource {
 use HasFilamentPermissions;
 protected static ?string $model=LocalizationLanguage::class;
 protected static ?string $navigationGroup='زبان و ترجمه';protected static ?string $navigationLabel='زبان‌ها';protected static ?string $modelLabel='زبان';protected static ?string $pluralModelLabel='زبان‌ها';protected static ?string $navigationIcon='heroicon-o-language';
 protected static function getViewPermission():string{return 'view-localization-languages';}
 protected static function getCreatePermission():string{return 'create-localization-languages';}
 protected static function getEditPermission():string{return 'edit-localization-languages';}
 protected static function getDeletePermission():string{return 'delete-localization-languages';}
 public static function canDelete($record):bool{return $record?->code!=='fa' && (auth('admin')->user()?->can(static::getDeletePermission()) ?? false);}
 public static function form(Form $form):Form{return $form->schema([
  Forms\Components\TextInput::make('code')->label('شناسه استاندارد زبان (BCP 47)')->placeholder('en یا zh-CN')->required()->regex('/^[a-z]{2,3}(?:-[a-zA-Z0-9]{2,8})*$/D')->maxLength(35)->unique(ignoreRecord:true)->disabledOn('edit')->dehydrated(fn($record)=>!$record),
  Forms\Components\TextInput::make('name')->label('نام فارسی')->required()->maxLength(100),
  Forms\Components\TextInput::make('native_name')->label('نام به خط بومی زبان')->required()->maxLength(100),
  Forms\Components\Select::make('direction')->label('جهت نوشتار')->options(['rtl'=>'راست به چپ','ltr'=>'چپ به راست'])->default('ltr')->required(),
  Forms\Components\Toggle::make('is_enabled')->label('فعال')->default(false),
  Forms\Components\TextInput::make('sort_order')->label('ترتیب نمایش')->numeric()->minValue(0)->default(10)->required(),
 ]);}
 public static function table(Table $table):Table{return $table->columns([
  Tables\Columns\TextColumn::make('code')->label('شناسه')->searchable(),Tables\Columns\TextColumn::make('name')->label('نام')->searchable(),Tables\Columns\TextColumn::make('native_name')->label('نام بومی'),Tables\Columns\IconColumn::make('is_enabled')->label('فعال')->boolean(),Tables\Columns\TextColumn::make('sort_order')->label('ترتیب')->sortable(),
 ])->actions([Tables\Actions\EditAction::make(),Tables\Actions\DeleteAction::make()->visible(fn($record)=>static::canDelete($record))])->defaultSort('sort_order');}
 public static function getPages():array{return ['index'=>LocalizationLanguageResource\Pages\ListLocalizationLanguages::route('/'),'create'=>LocalizationLanguageResource\Pages\CreateLocalizationLanguage::route('/create'),'edit'=>LocalizationLanguageResource\Pages\EditLocalizationLanguage::route('/{record}/edit')];}
}
