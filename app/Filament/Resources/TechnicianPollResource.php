<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TechnicianPollResource\Pages;
use App\Filament\Resources\TechnicianPollResource\RelationManagers;
use App\Models\TechnicianPoll;
use App\Models\Technician;
use App\Traits\HasFilamentPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TechnicianPollResource extends Resource
{
    use HasFilamentPermissions;
    protected static function getViewPermission(): string
    {
        return 'view-technician-polls';
    }
    protected static function getDeletePermission(): string
    {
        return 'delete-technician-polls';
    }
    protected static ?string $model = TechnicianPoll::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-bottom-center-text';

    protected static ?string $navigationLabel = 'نظرسنجی تکنسین‌ها';

    protected static ?string $modelLabel = 'نظر تکنسین';

    protected static ?string $pluralModelLabel = 'نظرسنجی تکنسین‌ها';

    protected static ?string $navigationGroup = 'نظرسنجی و بازخورد';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('اطلاعات تکنسین')
                    ->schema([
                        Forms\Components\Select::make('technician_id')
                            ->label('تکنسین')
                            ->relationship('technician', 'name')
                            ->getOptionLabelFromRecordUsing(
                                fn(Technician $record) =>
                                "{$record->name} - {$record->phone}"
                            )
                            ->searchable()
                            ->preload()
                            ->disabled()
                            ->dehydrated()
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('نظرات و پیشنهادات')
                    ->schema([
                        Forms\Components\Textarea::make('user_application')
                            ->label('نظر درباره اپلیکیشن کاربران')
                            ->rows(3)
                            ->disabled()
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('technician_application')
                            ->label('نظر درباره اپلیکیشن تکنسین‌ها')
                            ->rows(3)
                            ->disabled()
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('inner_personnel')
                            ->label('نظر درباره پرسنل داخلی')
                            ->rows(3)
                            ->disabled()
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('field_personnel')
                            ->label('نظر درباره پرسنل میدانی')
                            ->rows(3)
                            ->disabled()
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('other')
                            ->label('سایر نظرات و پیشنهادات')
                            ->rows(4)
                            ->disabled()
                            ->columnSpanFull(),
                    ])
                    ->columns(1),
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
                    ->searchable(),

                Tables\Columns\TextColumn::make('technician.phone')
                    ->label('شماره تماس')
                    ->sortable()
                    ->searchable()
                    ->copyable()
                    ->copyMessage('شماره کپی شد'),

                Tables\Columns\IconColumn::make('user_application')
                    ->label('اپ کاربران')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->getStateUsing(fn($record) => !empty($record->user_application))
                    ->tooltip(fn($record) => $record->user_application
                        ? mb_substr($record->user_application, 0, 50) . '...'
                        : 'نظری ثبت نشده'),

                Tables\Columns\IconColumn::make('technician_application')
                    ->label('اپ تکنسین‌ها')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->getStateUsing(fn($record) => !empty($record->technician_application))
                    ->tooltip(fn($record) => $record->technician_application
                        ? mb_substr($record->technician_application, 0, 50) . '...'
                        : 'نظری ثبت نشده'),

                Tables\Columns\IconColumn::make('inner_personnel')
                    ->label('پرسنل داخلی')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->getStateUsing(fn($record) => !empty($record->inner_personnel))
                    ->tooltip(fn($record) => $record->inner_personnel
                        ? mb_substr($record->inner_personnel, 0, 50) . '...'
                        : 'نظری ثبت نشده'),

                Tables\Columns\IconColumn::make('field_personnel')
                    ->label('پرسنل میدانی')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->getStateUsing(fn($record) => !empty($record->field_personnel))
                    ->tooltip(fn($record) => $record->field_personnel
                        ? mb_substr($record->field_personnel, 0, 50) . '...'
                        : 'نظری ثبت نشده'),

                Tables\Columns\IconColumn::make('other')
                    ->label('سایر نظرات')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->getStateUsing(fn($record) => !empty($record->other))
                    ->tooltip(fn($record) => $record->other
                        ? mb_substr($record->other, 0, 50) . '...'
                        : 'نظری ثبت نشده'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ ثبت')
                    ->jalaliDateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([

            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('مشاهده جزئیات'),

                Tables\Actions\DeleteAction::make()
                    ->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف'),
                ]),
            ]);
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
            'index' => Pages\ListTechnicianPolls::route('/'),
            'view' => Pages\ViewTechnicianPoll::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // نظرات فقط از طریق API ثبت می‌شوند
    }

    public static function canEdit($record): bool
    {
        return false; // نظرات قابل ویرایش نیستند
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }
}
