<?php
namespace App\Filament\Resources;
use App\Models\ProcurementConsultation;
use App\Traits\HasFilamentPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
class ProcurementConsultationResource extends Resource {
 use HasFilamentPermissions;
 protected static ?string $model=ProcurementConsultation::class;
 protected static ?string $navigationIcon='heroicon-o-chat-bubble-left-right';
 protected static ?string $navigationLabel='درخواست مشاوره';
 protected static ?string $modelLabel='درخواست مشاوره';
 protected static ?string $pluralModelLabel='درخواست‌های مشاوره';
 protected static ?int $navigationSort=2;
 protected static function getViewPermission():string { return 'view-procurement-consultations'; }
 protected static function getEditPermission():string { return 'edit-procurement-consultations'; }
 public static function canCreate():bool { return false; }
 public static function canDelete($record):bool { return false; }
 public static function canDeleteAny():bool { return false; }
 public static function getNavigationBadge():?string { $n=ProcurementConsultation::where('status','pending')->count();return $n?(string)$n:null; }
 public static function form(Form $form):Form { return $form->schema([
  Forms\Components\Placeholder::make('customer')->label('کاربر')->content(fn($record)=>$record?->user?->full_name ?? trim(($record?->user?->name??'').' '.($record?->user?->last_name??''))),
  Forms\Components\Placeholder::make('code')->label('شناسه کاربری')->content(fn($record)=>$record?->user?->code),
  Forms\Components\Placeholder::make('phone')->label('موبایل')->content(fn($record)=>$record?->user?->phone),
  Forms\Components\TextInput::make('title')->label('کالا / محصول')->disabled(),
  Forms\Components\TextInput::make('new_count')->label('تعداد آکبند')->disabled(),Forms\Components\TextInput::make('used_count')->label('تعداد کارکرده')->disabled(),
  Forms\Components\TextInput::make('budget_rial')->label('محدوده هزینه (ریال)')->disabled(),
  Forms\Components\Textarea::make('address')->label('آدرس')->disabled(),Forms\Components\Textarea::make('description')->label('توضیحات')->disabled(),
  Forms\Components\Select::make('status')->label('وضعیت')->options(['pending'=>'در انتظار تماس','contacted'=>'تماس گرفته شد','completed'=>'انجام شد'])->required(),
  Forms\Components\Textarea::make('admin_notes')->label('یادداشت پیگیری'),
 ]); }
 public static function table(Table $table):Table { return $table->columns([
  Tables\Columns\TextColumn::make('user.name')->label('نام کاربر')->searchable(),
  Tables\Columns\TextColumn::make('user.code')->label('شناسه کاربری')->searchable(),
  Tables\Columns\TextColumn::make('user.phone')->label('موبایل')->searchable(),
  Tables\Columns\TextColumn::make('title')->label('کالا'),
  Tables\Columns\TextColumn::make('budget_rial')->label('بودجه (ریال)')->numeric(),
  Tables\Columns\TextColumn::make('created_at')->label('زمان ثبت')->jalaliDateTime()->sortable(),
  Tables\Columns\TextColumn::make('status')->label('وضعیت')->formatStateUsing(fn($state)=>['pending'=>'در انتظار تماس','contacted'=>'تماس گرفته شد','completed'=>'انجام شد'][$state]??$state),
 ])->actions([Tables\Actions\EditAction::make()])->defaultSort('id','desc'); }
 public static function getPages():array { return ['index'=>ProcurementConsultationResource\Pages\ListProcurementConsultations::route('/'),'edit'=>ProcurementConsultationResource\Pages\EditProcurementConsultation::route('/{record}/edit')]; }
}
