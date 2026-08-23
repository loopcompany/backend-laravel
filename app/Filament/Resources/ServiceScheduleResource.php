<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceScheduleResource\Pages;
use App\Filament\Resources\ServiceScheduleResource\RelationManagers;
use App\Models\ServiceSchedule;
use App\Traits\HasFilamentPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ServiceScheduleResource extends Resource
{
    use HasFilamentPermissions;
    protected static ?string $model = ServiceSchedule::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'زمان‌بندی سرویس';

    protected static ?string $modelLabel = 'گزینه زمان‌بندی';

    protected static ?string $pluralModelLabel = 'گزینه‌های زمان‌بندی';

    protected static ?string $navigationGroup = 'تنظیمات';
    protected static function getViewPermission(): string
    {
        return 'view-service-schedules';
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('type')
                    ->label('نوع گزینه')
                    ->options([
                        'main' => 'اصلی (کوتاه/بلند مدت)',
                        'duration' => 'مدت زمان',
                        'time' => 'ساعت',
                        'file' => 'فایل',
                    ])
                    ->required()
                    ->reactive()
                    ->columnSpanFull(),

                Forms\Components\Select::make('term_type')
                    ->label('نوع مدت')
                    ->options([
                        'short_term' => 'کوتاه مدت',
                        'long_term' => 'بلند مدت',
                    ])
                    ->visible(fn($get) => in_array($get('type'), ['main', 'duration', 'time']))
                    ->required(fn($get) => in_array($get('type'), ['main', 'duration', 'time']))
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('label')
                    ->label('برچسب نمایشی (فارسی)')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('value')
                    ->label('مقدار (انگلیسی)')
                    ->maxLength(255)
                    ->helperText('مقدار منحصر به فرد برای این گزینه (مثلاً: 60_days_every_15)')
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('sort_order')
                    ->label('ترتیب نمایش')
                    ->numeric()
                    ->default(0)
                    ->required(),

                Forms\Components\Toggle::make('is_active')
                    ->label('فعال')
                    ->default(true)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('نوع')
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'main' => 'اصلی',
                        'duration' => 'مدت زمان',
                        'time' => 'ساعت',
                        'file' => 'فایل',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'main' => 'success',
                        'duration' => 'warning',
                        'time' => 'info',
                        'file' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('term_type')
                    ->label('مدت')
                    ->formatStateUsing(fn(?string $state): string => match ($state) {
                        'short_term' => 'کوتاه مدت',
                        'long_term' => 'بلند مدت',
                        default => '-',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('label')
                    ->label('برچسب')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('value')
                    ->label('مقدار')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('ترتیب')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('فعال')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ ایجاد')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('نوع')
                    ->options([
                        'main' => 'اصلی',
                        'duration' => 'مدت زمان',
                        'time' => 'ساعت',
                        'file' => 'فایل',
                    ]),

                Tables\Filters\SelectFilter::make('term_type')
                    ->label('مدت')
                    ->options([
                        'short_term' => 'کوتاه مدت',
                        'long_term' => 'بلند مدت',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('فعال')
                    ->trueLabel('فعال')
                    ->falseLabel('غیرفعال')
                    ->nullable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                // حذف دکمه Delete
            ])
            ->bulkActions([
                // حذف bulk actions
            ])
            ->defaultSort('type')
            ->defaultSort('sort_order');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServiceSchedules::route('/'),
            // 'create' => Pages\CreateServiceSchedule::route('/create'),
            'edit' => Pages\EditServiceSchedule::route('/{record}/edit'),
        ];
    }

    /**
     * غیرفعال کردن امکان ایجاد رکورد جدید
     */
    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * غیرفعال کردن امکان حذف رکورد
     */
    public static function canDelete($record): bool
    {
        return false;
    }

    /**
     * غیرفعال کردن امکان حذف گروهی
     */
    public static function canDeleteAny(): bool
    {
        return false;
    }
}
