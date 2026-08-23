<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ManpowerRequestResource\Pages;
use App\Models\ManpowerRequest;
use App\Models\Technician;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\HasFilamentPermissions;

class ManpowerRequestResource extends Resource
{
    use HasFilamentPermissions;

    protected static ?string $model = ManpowerRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'درخواست‌ها';

    protected static ?string $navigationLabel = 'درخواست‌های نیروی انسانی';

    protected static ?string $modelLabel = 'درخواست نیروی انسانی';

    protected static ?string $pluralModelLabel = 'درخواست‌های نیروی انسانی';

    protected static ?int $navigationSort = 4;

    protected static function getViewPermission(): string
    {
        return 'view-manpower-requests';
    }

    

    protected static function getEditPermission(): string
    {
        return 'edit-manpower-requests';
    }

    protected static function getDeletePermission(): string
    {
        return 'delete-manpower-requests';
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
                            ->label('نوع درخواست')
                            ->options([
                                'field' => 'نیروی میدانی',
                                'human' => 'نیروی انسانی',
                            ])
                            ->required()
                            ->disabled(),

                        Forms\Components\Textarea::make('description')
                            ->label('توضیحات')
                            ->required()
                            ->maxLength(5000)
                            ->rows(5)
                            ->disabled()
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
                    ->label('نوع درخواست')
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'field' => 'نیروی میدانی',
                        'human' => 'نیروی انسانی',
                        default => $state,
                    })
                    ->colors([
                        'primary' => 'field',
                        'info' => 'human',
                    ]),

                Tables\Columns\TextColumn::make('description')
                    ->label('توضیحات')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
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
                    ->label('نوع درخواست')
                    ->options([
                        'field' => 'نیروی میدانی',
                        'human' => 'نیروی انسانی',
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
            'index' => Pages\ListManpowerRequests::route('/'),
            'view' => Pages\ViewManpowerRequest::route('/{record}'),
            'edit' => Pages\EditManpowerRequest::route('/{record}/edit'),
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

