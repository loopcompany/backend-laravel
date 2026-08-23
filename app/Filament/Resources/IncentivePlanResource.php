<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IncentivePlanResource\Pages;
use App\Models\IncentivePlan;
use App\Models\Technician;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Traits\HasFilamentPermissions;

class IncentivePlanResource extends Resource
{
    use HasFilamentPermissions;

    protected static ?string $model = IncentivePlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';
    protected static ?string $navigationLabel = 'طرح‌های تشویقی';
    protected static ?string $modelLabel = 'طرح تشویقی';
    protected static ?string $pluralModelLabel = 'طرح‌های تشویقی';
    protected static ?string $navigationGroup = 'مدیریت تکنسین‌ها';
    protected static ?int $navigationSort = 4;

    protected static function getViewPermission(): string
    {
        return 'view-incentive-plans';
    }

    protected static function getCreatePermission(): string
    {
        return 'create-incentive-plans';
    }

    protected static function getEditPermission(): string
    {
        return 'edit-incentive-plans';
    }

    protected static function getDeletePermission(): string
    {
        return 'delete-incentive-plans';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('اطلاعات طرح تشویقی')
                    ->schema([
                        Forms\Components\Radio::make('send_mode')
                            ->label('نحوه ارسال')
                            ->required()
                            ->default('manual')
                            ->live()
                            ->options([
                                'manual' => 'انتخاب دستی تکنسین‌ها',
                                'field' => 'ارسال برای تکنسین‌های میدانی',
                                'internal' => 'ارسال برای تکنسین‌های داخلی',
                            ])
                            ->helperText('می‌توانید به‌صورت دستی انتخاب کنید یا برای یک گروه از تکنسین‌ها ارسال کنید.')
                            ->columnSpanFull(),

                        Forms\Components\Select::make('technician_ids')
                            ->label('تکنسین‌ها')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->visible(fn (Get $get) => $get('send_mode') === 'manual')
                            ->required(fn (Get $get) => $get('send_mode') === 'manual')
                            ->options(function () {
                                return Technician::query()
                                    ->limit(100)
                                    ->get()
                                    ->mapWithKeys(fn (Technician $record) => [
                                        $record->id => "{$record->name} - {$record->phone}" .
                                            ($record->melicode ? " (کد ملی: {$record->melicode})" : ''),
                                    ]);
                            })
                            ->getSearchResultsUsing(function (string $search) {
                                return Technician::query()
                                    ->where(function ($query) use ($search) {
                                        $query->where('name', 'like', "%{$search}%")
                                            ->orWhere('phone', 'like', "%{$search}%")
                                            ->orWhere('melicode', 'like', "%{$search}%");
                                    })
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(fn (Technician $record) => [
                                        $record->id => "{$record->name} - {$record->phone}" .
                                            ($record->melicode ? " (کد ملی: {$record->melicode})" : ''),
                                    ]);
                            })
                            ->helperText('می‌توانید چند تکنسین را انتخاب کنید')
                            ->columnSpanFull(),

                        Forms\Components\Placeholder::make('send_mode_hint')
                            ->label('توضیح')
                            ->content(function (Get $get) {
                                return match ($get('send_mode')) {
                                    'field' => 'این طرح برای همه تکنسین‌هایی که نوع آن‌ها "تکنسین میدانی" است ثبت می‌شود.',
                                    'internal' => 'این طرح برای همه تکنسین‌هایی که نوع آن‌ها "تکنسین داخلی" است ثبت می‌شود.',
                                    default => 'در این حالت باید تکنسین‌ها را به‌صورت دستی انتخاب کنید.',
                                };
                            })
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('description')
                            ->label('توضیحات طرح')
                            ->required()
                            ->rows(5)
                            ->placeholder('جزئیات طرح تشویقی را وارد کنید...')
                            ->helperText('توضیحات کامل در مورد این طرح تشویقی')
                            ->columnSpanFull(),

                        Forms\Components\DateTimePicker::make('end_at')
                            ->label('تاریخ انقضای طرح')
                            ->jalali(),

                        Forms\Components\Select::make('status')
                            ->label('وضعیت')
                            ->required()
                            ->default(IncentivePlan::STATUS_PENDING)
                            ->options(IncentivePlan::getStatusOptions())
                            ->helperText('وضعیت فعلی این طرح تشویقی'),
                    ])
                    ->columns(2),
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

                Tables\Columns\TextColumn::make('technician.name')
                    ->label('نام تکنسین')
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(fn ($record) => $record->technician?->name ?? '-'),

                Tables\Columns\TextColumn::make('technician.referral_code')
                    ->label('کد تکنسین')
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(fn ($record) => $record->technician?->referral_code ?? '-'),

                Tables\Columns\TextColumn::make('technician.phone')
                    ->label('شماره تماس')
                    ->sortable()
                    ->searchable()
                    ->copyable()
                    ->copyMessage('شماره کپی شد')
                    ->copyMessageDuration(1500),

                Tables\Columns\TextColumn::make('description')
                    ->label('توضیحات')
                    ->limit(50)
                    ->searchable()
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();

                        if (strlen($state) <= 50) {
                            return null;
                        }

                        return $state;
                    }),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('وضعیت')
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->end_at && now()->isAfter($record->end_at)) {
                            return 'منقضی شده';
                        }

                        return match ($state) {
                            IncentivePlan::STATUS_PENDING => 'فعال',
                            IncentivePlan::STATUS_USED => 'استفاده شده',
                            default => 'نامشخص',
                        };
                    })
                    ->colors([
                        'success' => IncentivePlan::STATUS_PENDING,
                        'info' => IncentivePlan::STATUS_USED,
                        'danger' => fn ($record) => $record->end_at && now()->isAfter($record->end_at),
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_at')
                    ->label('تاریخ انقضا')
                    ->jalaliDateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ ایجاد')
                    ->jalaliDateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('آخرین بروزرسانی')
                    ->jalaliDateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('وضعیت')
                    ->options(IncentivePlan::getStatusOptions())
                    ->placeholder('همه وضعیت‌ها'),

                Tables\Filters\SelectFilter::make('technician_id')
                    ->label('تکنسین')
                    ->relationship('technician', 'name')
                    ->getOptionLabelFromRecordUsing(fn (Technician $record) => $record->name)
                    ->searchable()
                    ->preload()
                    ->placeholder('همه تکنسین‌ها'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('مشاهده'),
                Tables\Actions\EditAction::make()->label('ویرایش'),
                Tables\Actions\DeleteAction::make()->label('حذف'),

                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('mark_used')
                        ->label('علامت‌گذاری به عنوان استفاده شده')
                        ->icon('heroicon-o-check-circle')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('تغییر وضعیت به استفاده شده')
                        ->modalDescription('آیا مطمئن هستید که می‌خواهید این طرح را به عنوان استفاده شده علامت‌گذاری کنید؟')
                        ->modalSubmitActionLabel('بله، تغییر دهید')
                        ->modalCancelActionLabel('انصراف')
                        ->action(function (IncentivePlan $record) {
                            $record->update(['status' => IncentivePlan::STATUS_USED]);
                        })
                        ->visible(fn (IncentivePlan $record) => $record->status !== IncentivePlan::STATUS_USED)
                        ->successNotificationTitle('وضعیت با موفقیت به "استفاده شده" تغییر یافت'),

                    Tables\Actions\Action::make('mark_active')
                        ->label('فعال‌سازی مجدد')
                        ->icon('heroicon-o-arrow-path')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('تغییر وضعیت به فعال')
                        ->modalDescription('آیا مطمئن هستید که می‌خواهید این طرح را مجدداً فعال کنید؟')
                        ->modalSubmitActionLabel('بله، فعال کن')
                        ->modalCancelActionLabel('انصراف')
                        ->action(function (IncentivePlan $record) {
                            $record->update(['status' => IncentivePlan::STATUS_PENDING]);
                        })
                        ->visible(fn (IncentivePlan $record) => $record->status !== IncentivePlan::STATUS_PENDING)
                        ->successNotificationTitle('وضعیت با موفقیت به "فعال" تغییر یافت'),

                    Tables\Actions\DeleteAction::make()->label('حذف'),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('mark_used')
                        ->label('علامت‌گذاری به عنوان استفاده شده')
                        ->icon('heroicon-o-check-circle')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('تغییر وضعیت دسته‌جمعی')
                        ->modalDescription('آیا مطمئن هستید که می‌خواهید طرح‌های انتخاب شده را به عنوان استفاده شده علامت‌گذاری کنید؟')
                        ->modalSubmitActionLabel('بله، تغییر دهید')
                        ->modalCancelActionLabel('انصراف')
                        ->action(function ($records) {
                            $records->each->update(['status' => IncentivePlan::STATUS_USED]);
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotificationTitle('وضعیت طرح‌های انتخابی با موفقیت تغییر یافت'),

                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIncentivePlans::route('/'),
            'create' => Pages\CreateIncentivePlan::route('/create'),
            'edit' => Pages\EditIncentivePlan::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('status', IncentivePlan::STATUS_PENDING)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}
