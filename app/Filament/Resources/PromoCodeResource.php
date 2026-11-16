<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PromoCodeResource\Pages;
use App\Models\PromoCode;
use App\Traits\HasFilamentPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PromoCodeResource extends Resource
{
    use HasFilamentPermissions;

    protected static ?string $model = PromoCode::class;
    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationGroup = 'مدیریت تخفیف‌ها';
    protected static ?string $navigationLabel = 'کدهای تشویقی';
    protected static ?string $modelLabel = 'کد تشویقی';
    protected static ?string $pluralModelLabel = 'کدهای تشویقی';

    protected static function getViewPermission(): string
    {
        return 'view-promo-codes';
    }

    protected static function getCreatePermission(): string
    {
        return 'create-promo-codes';
    }

    protected static function getEditPermission(): string
    {
        return 'edit-promo-codes';
    }

    protected static function getDeletePermission(): string
    {
        return 'delete-promo-codes';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('category')->label('دسته‌بندی کد')->maxLength(100),
            Forms\Components\Section::make('اطلاعات کد تشویقی')
                ->schema([
                    Forms\Components\Placeholder::make('generated_code')
                        ->label('کد تشویقی')
                        ->content(fn (?PromoCode $record): string => $record?->code ?? 'پس از ذخیره به‌صورت خودکار تولید می‌شود')
                        ->visibleOn('create'),

                    Forms\Components\TextInput::make('code')
                        ->label('کد تشویقی')
                        ->disabled()
                        ->dehydrated(false)
                        ->visibleOn('edit')
                        ->helperText('این کد توسط سیستم تولید شده و قابل ویرایش نیست.'),

                    Forms\Components\TextInput::make('discount_percent')
                        ->label('درصد تخفیف')
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->maxValue(100)
                        ->required()
                        ->suffix('%'),

                    Forms\Components\Toggle::make('is_active')
                        ->label('فعال')
                        ->default(true)
                        ->required(),

                    Forms\Components\DateTimePicker::make('expires_at')
                        ->label('تاریخ انقضا')
                        ->jalali()
                        ->nullable()
                        ->helperText('خالی یعنی بدون تاریخ انقضا.'),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('category')->label('دسته‌بندی')->searchable(),
                Tables\Columns\TextColumn::make('deliveries_count')->label('تعداد ارسال/ارتباط')->counts('deliveries'),
                Tables\Columns\TextColumn::make('code')
                    ->label('کد')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('discount_percent')
                    ->label('درصد تخفیف')
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('فعال')
                    ->boolean(),
                Tables\Columns\TextColumn::make('usages_count')
                    ->label('تعداد استفاده')
                    ->counts('usages')
                    ->sortable(),
                Tables\Columns\TextColumn::make('expires_at')
                    ->label('تاریخ انقضا')
                    ->jalaliDateTime()
                    ->placeholder('بدون انقضا')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ ایجاد')
                    ->jalaliDateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('وضعیت'),
            ])
            ->actions([
                Tables\Actions\Action::make('send')->label('ارسال / ثبت ارتباط')->visible(fn ($record)=>static::canEdit($record))->authorize(fn ($record)=>static::canEdit($record))->form([
                    Forms\Components\Select::make('user_id')->label('کاربر')->searchable()->required()->options(fn()=>\App\Models\User::limit(200)->pluck('name','id'))->getSearchResultsUsing(fn (string $search)=>\App\Models\User::where('name','like',"%$search%")->orWhere('code','like',"%$search%")->orWhere('email','like',"%$search%")->limit(50)->get()->mapWithKeys(fn ($u)=>[$u->id=>$u->name.' | '.$u->code])->all()),
                    Forms\Components\Select::make('channel')->label('روش')->options(['sms'=>'پیامک','phone'=>'تلفنی','in_person'=>'حضوری'])->required(),
                ])->action(fn ($record,$data)=>app(\App\Services\CodeDeliveryService::class)->issue($record,\App\Models\User::findOrFail($data['user_id']),auth('admin')->id(),$data['channel'],$record->category)),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array { return [\App\Filament\RelationManagers\CodeDeliveriesRelationManager::class]; }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPromoCodes::route('/'),
            'create' => Pages\CreatePromoCode::route('/create'),
            'edit' => Pages\EditPromoCode::route('/{record}/edit'),
        ];
    }
}
