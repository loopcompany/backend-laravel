<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EducationRequestResource\Pages;
use App\Models\EducationRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\HasFilamentPermissions;

class EducationRequestResource extends Resource
{
    use HasFilamentPermissions;

    protected static ?string $model = EducationRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'درخواست‌های آموزش';

    protected static ?string $modelLabel = 'درخواست آموزش';

    protected static ?string $pluralModelLabel = 'درخواست‌های آموزش';

    protected static ?string $navigationGroup = 'درخواست‌ها';

    protected static ?int $navigationSort = 1;

    protected static function getViewPermission(): string
    {
        return 'view-education-requests';
    }

    protected static function getEditPermission(): string
    {
        return 'edit-education-requests';
    }

    protected static function getDeletePermission(): string
    {
        return 'delete-education-requests';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('اطلاعات تکنسین')
                    ->schema([
                        Forms\Components\Select::make('technician_id')
                            ->label('تکنسین')
                            ->relationship('technician', 'id')
                            ->getOptionLabelFromRecordUsing(
                                fn($record) =>
                                $record->name . ' - ' . $record->phone
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(fn($livewire) => $livewire instanceof Pages\EditEducationRequest),
                    ]),

                Forms\Components\Section::make('جزئیات درخواست')
                    ->schema([
                        Forms\Components\TextInput::make('section')
                            ->label('بخش/موضوع')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('description')
                            ->label('توضیحات')
                            ->required()
                            ->rows(5)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('وضعیت')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('وضعیت درخواست')
                            ->options([
                                0 => 'در انتظار بررسی',
                                1 => 'تأیید شده',
                                2 => 'رد شده',
                            ])
                            ->required()
                            ->default(0)
                            ->native(false),
                    ]),
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
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('technician.name')
                    ->label('نام تکنسین')
                    ->searchable()
                    ->sortable()
                    ->default('تکنسین ناشناس'),

                Tables\Columns\TextColumn::make('technician.phone')
                    ->label('شماره تماس')
                    ->searchable()
                    ->default('-'),

                Tables\Columns\TextColumn::make('section')
                    ->label('بخش/موضوع')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 30) {
                            return null;
                        }
                        return $state;
                    }),

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
                        0 => 'در انتظار',
                        1 => 'تأیید شده',
                        2 => 'رد شده',
                        default => 'نامشخص',
                    })
                    ->colors([
                        'warning' => '0',
                        'success' => '1',
                        'danger' => '2',
                    ])
                    ->sortable(),

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
                Tables\Filters\SelectFilter::make('status')
                    ->label('وضعیت')
                    ->options([
                        0 => 'در انتظار بررسی',
                        1 => 'تأیید شده',
                        2 => 'رد شده',
                    ])
                    ->native(false),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->jalali()
                            ->label('از تاریخ'),
                        Forms\Components\DatePicker::make('created_until')
                            ->jalali()
                            ->label('تا تاریخ'),
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
                Tables\Actions\ViewAction::make()
                    ->label('مشاهده'),
                Tables\Actions\EditAction::make()
                    ->label('ویرایش'),
                Tables\Actions\DeleteAction::make()
                    ->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف انتخاب شده‌ها'),

                    Tables\Actions\BulkAction::make('approve')
                        ->label('تأیید همه')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn($records) => $records->each->update(['status' => 1]))
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('reject')
                        ->label('رد همه')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn($records) => $records->each->update(['status' => 2]))
                        ->deselectRecordsAfterCompletion(),
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
            'index' => Pages\ListEducationRequests::route('/'),
            'view' => Pages\ViewEducationRequest::route('/{record}'),
            'edit' => Pages\EditEducationRequest::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 0)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::where('status', 0)->count() > 0 ? 'warning' : 'success';
    }
}
