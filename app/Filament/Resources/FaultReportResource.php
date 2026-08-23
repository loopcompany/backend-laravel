<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FaultReportResource\Pages;
use App\Models\FaultReport;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\HasFilamentPermissions;
use Morilog\Jalali\Jalalian;

class FaultReportResource extends Resource
{
    use HasFilamentPermissions;

    protected static ?string $model = FaultReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $navigationGroup = 'گزارشات';

    protected static ?string $navigationLabel = 'گزارش‌های خرابی';

    protected static ?string $modelLabel = 'گزارش خرابی';

    protected static ?string $pluralModelLabel = 'گزارش‌های خرابی';

    protected static function getViewPermission(): string
    {
        return 'view-fault-reports';
    }


    protected static function getCreatePermission(): string
    {
        return 'create-fault-reports';
    }



    protected static function getEditPermission(): string
    {
        return 'edit-fault-reports';
    }

    protected static function getDeletePermission(): string
    {
        return 'delete-fault-reports';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('اطلاعات کاربر و سفارش')
                    ->schema([
                        Forms\Components\Select::make('user_id')

                            ->label('کاربر')
                            ->relationship('user', 'name')
                            ->getOptionLabelFromRecordUsing(fn($record) => "{$record->name} - {$record->phone}")
                            ->searchable(['name', 'phone'])
                            ->preload()
                            ->disabledOn('edit')
                            ->required()
                            ->columnSpan(1),
                        Forms\Components\Select::make('order_id')
                            ->label('سفارش')
                            ->required()
                            ->relationship('order', 'id')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->disabledOn('edit')
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('اطلاعات محصول و خدمات')
                    ->schema([
                        Forms\Components\TextInput::make('product_name')
                            ->label('نام محصول')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),

                        Forms\Components\DatePicker::make('ordered_at')
                            ->label('تاریخ سفارش')
                            ->nullable()
                            ->jalali()
                            ->formatStateUsing(
                                fn($state) => $state
                                ? $state
                                : null
                            )
                            // از Carbon (میلادی) به جلالی برای ذخیره
                            ->dehydrateStateUsing(
                                fn($state) => $state
                                ? Jalalian::fromDateTime(Carbon::parse($state))->format('Y/m/d')
                                : null
                            )
                            ->columnSpan(1),

                        Forms\Components\DatePicker::make('delivered_at')
                            ->label('تاریخ تحویل')
                            ->nullable()
                            ->jalali()
                            ->formatStateUsing(
                                fn($state) => $state
                                ? $state
                                : null
                            )
                            ->dehydrateStateUsing(
                                fn($state) => $state
                                ? Jalalian::fromDateTime(Carbon::parse($state))->format('Y/m/d')
                                : null
                            )
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('اطلاعات تکنسین و هزینه')
                    ->schema([
                        Forms\Components\TextInput::make('technician_code')
                            ->label('کد تکنسین')
                            ->maxLength(50)
                            ->nullable()
                            ->helperText('کد ارجاع تکنسین')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('paid_price')
                            ->label('مبلغ پرداختی (تومان)')
                            ->numeric()
                            ->nullable()
                            ->suffix('تومان')
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('شرح خرابی')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('توضیحات خرابی')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('شناسه')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('نام - کد کاربری')
                    ->state(function ($record) {
                        return $record->user
                            ? "{$record->user->name} - {$record->user->code}"
                            : 'نامشخص';
                    }),

                Tables\Columns\TextColumn::make('user.phone')
                    ->label('شماره کاربر')
                    ->searchable()
                    ->copyable()
                    ->default('-'),

                Tables\Columns\TextColumn::make('order_id')
                    ->label('شناسه سفارش')
                    ->searchable()
                    ->sortable()
                    ->default('-'),

                Tables\Columns\TextColumn::make('order.user_address.city')
                    ->label('شهر')
                    ->searchable()
                    ->default('-'),

                Tables\Columns\TextColumn::make('order.user_address.region')
                    ->label('منطقه')
                    ->searchable()
                    ->default('-'),

                Tables\Columns\TextColumn::make('product_name')
                    ->label('محصول')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn($record) => $record->product_name),

                Tables\Columns\TextColumn::make('technician_code')
                    ->label('کد تکنسین')
                    ->searchable()
                    ->default('-'),

                Tables\Columns\TextColumn::make('technician_name')
                    ->label('نام تکنسین')
                    ->default('-')
                    ->color(fn($state) => $state === 'تکنسین یافت نشد' || $state === 'کد تکنسین ثبت نشده' ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('paid_price')
                    ->label('مبلغ')
                    ->formatStateUsing(fn($state) => $state ? number_format($state) . ' تومان' : '-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('ordered_at')
                    ->label('تاریخ سفارش')
                    ->default('-'),

                Tables\Columns\TextColumn::make('delivered_at')
                    ->label('تاریخ تحویل')
                    ->default('-'),

                Tables\Columns\TextColumn::make('order.user_address.address')
                    ->label('آدرس')
                    ->limit(40)
                    ->tooltip(fn($record) => $record->order?->user_address?->address ?? ''),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ ثبت گزارش')
                    ->jalaliDateTime('Y/m/d H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('از تاریخ')
                            ->jalali(),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('تا تاریخ')
                            ->jalali(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('مشاهده'),
                Tables\Actions\EditAction::make()->label('ویرایش'),
                Tables\Actions\DeleteAction::make()->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('حذف انتخاب شده‌ها'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFaultReports::route('/'),
            'create' => Pages\CreateFaultReport::route('/create'),
            'view' => Pages\ViewFaultReport::route('/{record}'),
            'edit' => Pages\EditFaultReport::route('/{record}/edit'),
        ];
    }
}