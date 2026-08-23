<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TerminationRequestResource\Pages;
use App\Models\TerminationRequest;
use App\Models\Technician;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\HasFilamentPermissions;

class TerminationRequestResource extends Resource
{
    use HasFilamentPermissions;

    protected static ?string $model = TerminationRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-x-circle';

    protected static ?string $navigationGroup = 'درخواست‌ها';

    protected static ?string $navigationLabel = 'درخواست‌های قطع همکاری';

    protected static ?string $modelLabel = 'درخواست قطع همکاری';

    protected static ?string $pluralModelLabel = 'درخواست‌های قطع همکاری';

    protected static ?int $navigationSort = 6;

    protected static function getViewPermission(): string
    {
        return 'view-termination-requests';
    }

    protected static function getCreatePermission(): string
    {
        return 'create-termination-requests';
    }

    protected static function getEditPermission(): string
    {
        return 'edit-termination-requests';
    }

    protected static function getDeletePermission(): string
    {
        return 'delete-termination-requests';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('اطلاعات تکنسین')
                    ->schema([
                        Forms\Components\Select::make('technician_id')
                            ->label('تکنسین')
                            ->options(
                                Technician::all()
                                    ->mapWithKeys(fn($technician) => [
                                        $technician->id => $technician->name . ' (' . $technician->phone . ')'
                                    ])
                            )
                            ->required()
                            ->disabled(),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('جزئیات درخواست')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('نوع قطع همکاری')
                            ->options([
                                'temporary' => 'موقت',
                                'permanent' => 'دائم',
                            ])
                            ->required()
                            ->disabled(),

                        Forms\Components\TextInput::make('start_date')
                            ->label('تاریخ شروع')
                            ->required()
                            ->disabled(),

                        Forms\Components\TextInput::make('end_date')
                            ->label('تاریخ پایان')
                            ->disabled(),

                        Forms\Components\Textarea::make('description')
                            ->label('دلیل قطع همکاری')
                            ->disabled()
                            ->maxLength(5000)
                            ->rows(5)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('وضعیت')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('وضعیت درخواست')
                            ->options([
                                0 => 'در انتظار بررسی',
                                1 => 'تأیید شده',
                                2 => 'رد شده',
                            ])
                            ->default(0)
                            ->required(),
                    ])
                    ->columns(1),
                Forms\Components\Textarea::make('response')
                            ->label('توضیحات ادمین') 
                            ->maxLength(5000)
                            ->rows(5)
                            ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('شناسه')
                    ->sortable(),

                Tables\Columns\TextColumn::make('technician.name')
                    ->label('نام تکنسین')
                    ->searchable()
                    ->sortable()
                    ->default('تکنسین ناشناس'),

                Tables\Columns\TextColumn::make('technician.phone')
                    ->label('شماره تماس')
                    ->searchable()
                    ->default('-'),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('نوع')
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'temporary' => 'موقت',
                        'permanent' => 'دائم',
                        default => $state,
                    })
                    ->colors([
                        'warning' => 'temporary',
                        'danger' => 'permanent',
                    ]),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('تاریخ شروع')
                    ->date('Y/m/d')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('تاریخ پایان')
                    ->date('Y/m/d')
                    ->placeholder('-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('دلیل')
                    ->limit(40)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 40) {
                            return null;
                        }
                        return $state;
                    }),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('وضعیت')
                    ->formatStateUsing(fn(int $state): string => match ($state) {
                        0 => 'در انتظار بررسی',
                        1 => 'تأیید شده',
                        2 => 'رد شده',
                        default => 'نامشخص',
                    })
                    ->colors([
                        'warning' => 0,
                        'success' => 1,
                        'danger' => 2,
                    ]),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ ثبت')
                    ->jalaliDateTime('Y/m/d H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('آخرین بروزرسانی')
                    ->jalaliDateTime('Y/m/d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('نوع')
                    ->options([
                        'temporary' => 'موقت',
                        'permanent' => 'دائم',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->label('وضعیت')
                    ->options([
                        0 => 'در انتظار بررسی',
                        1 => 'تأیید شده',
                        2 => 'رد شده',
                    ]),
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
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('approve')
                        ->label('تأیید همه')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->update(['status' => 1]);
                        }),

                    Tables\Actions\BulkAction::make('reject')
                        ->label('رد همه')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->update(['status' => 2]);
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListTerminationRequests::route('/'),
            'view' => Pages\ViewTerminationRequest::route('/{record}'),
            'edit' => Pages\EditTerminationRequest::route('/{record}/edit'),
        ];
    }
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 0)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}

