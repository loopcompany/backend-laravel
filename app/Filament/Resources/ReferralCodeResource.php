<?php
namespace App\Filament\Resources;

use App\Filament\Resources\ReferralCodeResource\Pages;
use App\Models\{ReferralCode,User};
use App\Traits\HasFilamentPermissions;
use Filament\{Forms,Tables};
use Filament\Forms\{Form,Get};
use Filament\Tables\Table;
use Filament\Resources\Resource;

class ReferralCodeResource extends Resource
{
    use HasFilamentPermissions;
    // Retired business feature: keep historic rows for accounting, expose no management routes.
    public static function shouldRegisterNavigation(): bool { return false; }
    public static function canViewAny(): bool { return false; }
    public static function canView($record): bool { return false; }
    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; }
    public static function canDeleteAny(): bool { return false; }

    protected static ?string $model = ReferralCode::class;
    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationGroup = 'مدیریت کاربران';
    protected static ?string $navigationLabel = 'کدهای معرف';
    protected static ?string $modelLabel = 'کد معرف';
    protected static ?string $pluralModelLabel = 'کدهای معرف';
    protected static function getViewPermission(): string { return 'view-user'; }
    protected static function getCreatePermission(): string { return 'create-user'; }
    protected static function getEditPermission(): string { return 'edit-user'; }
    protected static function getDeletePermission(): string { return 'delete-user'; }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('کد معرف یک‌بارمصرف کاربر ویژه')->schema([
                Forms\Components\Select::make('user_id')->label('کاربر ویژه سازمانی / شرکتی')->required()->searchable()
                    ->disabled(fn (?ReferralCode $record)=>$record?->used_order_id !== null)
                    ->getSearchResultsUsing(fn (string $search)=>ReferralCode::eligibleUsers()->with('organization')->where(function ($q) use ($search) {
                        $q->where('name','like',"%$search%") ->orWhere('last_name','like',"%$search%") ->orWhere('email','like',"%$search%") ->orWhere('code','like',"%$search%")
                          ->orWhereHas('organization',fn ($o)=>$o->where('organization_name','like',"%$search%")->orWhere('business_name','like',"%$search%"));
                    })->limit(50)->get()->mapWithKeys(fn ($u)=>[$u->id=>self::userLabel($u)])->all())
                    ->getOptionLabelUsing(fn ($value)=>($u=User::with('organization')->find($value)) ? self::userLabel($u) : null)
                    ->helperText('دارای شناسه کاربری، ویژه و ثبت‌شده از اپ، دسکتاپ یا سایت؛ ورود کد در اپ اختیاری است.'),
                Forms\Components\Placeholder::make('generated_code')->label('کد')->content(fn (?ReferralCode $record)=>$record?->code ?? 'پس از ذخیره تولید و خودکار به صف پیامک می‌رود.'),
                Forms\Components\Select::make('commission_mode')->label('روش محاسبه پورسانت')->options(['table'=>'جدول کارفرما (فقط مبالغ دقیق جدول)','manual'=>'مبلغ دستی'])->default('table')->required()->live()->disabled(fn (?ReferralCode $record)=>(bool)$record?->used_order_id),
                Forms\Components\TextInput::make('manual_commission_amount')->label('پورسانت دستی (تومان)')->numeric()->integer()->minValue(0)->required(fn (Get $get)=>$get('commission_mode')==='manual')->visible(fn (Get $get)=>$get('commission_mode')==='manual')->disabled(fn (?ReferralCode $record)=>(bool)$record?->used_order_id),
            ])->columns(2),
        ]);
    }
    public static function userLabel(User $u): string { return trim($u->name.' '.$u->last_name).' | '.$u->code.' | '.($u->organization?->organization_name ?: $u->organization?->business_name); }
    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('code')->label('کد معرف')->searchable()->copyable(),
            Tables\Columns\TextColumn::make('user.name')->label('نام کاربر')->searchable(),
            Tables\Columns\TextColumn::make('user.code')->label('شناسه کاربری')->searchable(),
            Tables\Columns\TextColumn::make('user.email')->label('نام کاربری')->searchable(),
            Tables\Columns\TextColumn::make('user.organization.organization_name')->label('سازمان')->searchable(),
            Tables\Columns\TextColumn::make('user.organization.business_name')->label('شرکت')->searchable(),
            Tables\Columns\TextColumn::make('status')->label('وضعیت')->formatStateUsing(fn ($state)=>ReferralCode::statuses()[$state] ?? $state),
            Tables\Columns\TextColumn::make('created_at')->label('تاریخ صدور')->jalaliDateTime()->sortable(),
            Tables\Columns\TextColumn::make('deliveries_count')->label('سوابق ارتباط')->counts('deliveries'),
            Tables\Columns\TextColumn::make('used_at')->label('زمان مصرف')->jalaliDateTime()->placeholder('—'),
        ])->filters([Tables\Filters\SelectFilter::make('status')->label('وضعیت')->options(ReferralCode::statuses())])
          ->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\Action::make('communication')->label('ارسال / ثبت ارتباط')->visible(fn ($record)=>static::canEdit($record))->authorize(fn ($record)=>static::canEdit($record))->form([
                Forms\Components\Select::make('channel')->label('روش')->options(['sms'=>'پیامک','phone'=>'ثبت تماس تلفنی','in_person'=>'ثبت تحویل حضوری'])->required(),
            ])->action(fn ($record,$data)=>app(\App\Services\CodeDeliveryService::class)->issue($record,$record->user,auth('admin')->id(),$data['channel'])),
          ])->defaultSort('id','desc');
    }
    public static function getRelations(): array { return [\App\Filament\RelationManagers\CodeDeliveriesRelationManager::class]; }
    public static function getPages(): array { return ['index'=>Pages\ListReferralCodes::route('/'),'create'=>Pages\CreateReferralCode::route('/create'),'edit'=>Pages\EditReferralCode::route('/{record}/edit')]; }
}
