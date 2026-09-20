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
    protected static ?string $navigationLabel = 'کدهای تخفیف جدید';
    protected static ?string $modelLabel = 'کد تخفیف';
    protected static ?string $pluralModelLabel = 'کدهای تخفیف جدید';

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
            Forms\Components\Section::make('اطلاعات کد تخفیف')
                ->schema([
                    Forms\Components\Placeholder::make('generated_code')
                        ->label('کد تخفیف')
                        ->content(fn (?PromoCode $record): string => $record?->code ?? 'پس از ذخیره به‌صورت خودکار تولید می‌شود')
                        ->visibleOn('create'),

                    Forms\Components\TextInput::make('code')
                        ->label('کد تخفیف')
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPromoCodes::route('/'),
            'create' => Pages\CreatePromoCode::route('/create'),
            'edit' => Pages\EditPromoCode::route('/{record}/edit'),
        ];
    }
}
