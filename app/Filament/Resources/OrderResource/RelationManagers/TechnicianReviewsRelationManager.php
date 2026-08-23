<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;
use App\Models\Admin;

class TechnicianReviewsRelationManager extends RelationManager
{
    protected static string $relationship = 'technicianReviews';
    protected static ?string $title = 'نظرات و امتیازات کاربر';
    protected static ?string $modelLabel = 'نظر';
    protected static ?string $pluralModelLabel = 'نظرات';

    // کنترل دسترسی
    public function canView(Model $record): bool
    {
        return auth('admin')->check();
    }

    public function canCreate(): bool
    {
        return auth('admin')->check();
    }

    public function canEdit(Model $record): bool
    {
        return auth('admin')->check();
    }

    public function canDelete(Model $record): bool
    {
        return auth('admin')->check();
    }

    public function canDeleteAny(): bool
    {
        return auth('admin')->check();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('اطلاعات نظردهنده')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('کاربر')
                            ->relationship('user', 'phone')
                            ->searchable()
                            ->required()
                            ->disabled(fn (?string $operation) => $operation === 'edit'),

                        Forms\Components\Select::make('technician_id')
                            ->label('تکنسین')
                            ->relationship('technician', 'name')
                            ->searchable()
                            ->required()
                            ->disabled(fn (?string $operation) => $operation === 'edit'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('امتیازات')
                    ->schema([
                        Forms\Components\Select::make('application_rate')
                            ->label('امتیاز اپلیکیشن')
                            ->options([
                                1 => '⭐ 1 ستاره',
                                2 => '⭐⭐ 2 ستاره',
                                3 => '⭐⭐⭐ 3 ستاره',
                                4 => '⭐⭐⭐⭐ 4 ستاره',
                                5 => '⭐⭐⭐⭐⭐ 5 ستاره',
                            ])
                            ->required()
                            ->native(false),

                        Forms\Components\Select::make('technician_rate')
                            ->label('امتیاز تکنسین')
                            ->options([
                                1 => '⭐ 1 ستاره',
                                2 => '⭐⭐ 2 ستاره',
                                3 => '⭐⭐⭐ 3 ستاره',
                                4 => '⭐⭐⭐⭐ 4 ستاره',
                                5 => '⭐⭐⭐⭐⭐ 5 ستاره',
                            ])
                            ->required()
                            ->native(false),

                        Forms\Components\Select::make('support_rate')
                            ->label('امتیاز پشتیبانی')
                            ->options([
                                1 => '⭐ 1 ستاره',
                                2 => '⭐⭐ 2 ستاره',
                                3 => '⭐⭐⭐ 3 ستاره',
                                4 => '⭐⭐⭐⭐ 4 ستاره',
                                5 => '⭐⭐⭐⭐⭐ 5 ستاره',
                            ])
                            ->required()
                            ->native(false),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('نظر و توضیحات')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('توضیحات نظر')
                            ->rows(4)
                            ->nullable()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('نام کاربر امتیاز دهنده')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('user.code')
                    ->label('کد کاربر امتیاز دهنده')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('technician.name')
                    ->label('نام تکنسین')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('application_rate')
                    ->label('امتیاز اپ')
                    ->formatStateUsing(fn (int $state): string => str_repeat('⭐', $state))
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('technician_rate')
                    ->label('امتیاز تکنسین')
                    ->formatStateUsing(fn (int $state): string => str_repeat('⭐', $state))
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('support_rate')
                    ->label('امتیاز پشتیبانی')
                    ->formatStateUsing(fn (int $state): string => str_repeat('⭐', $state))
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('description')
                    ->label('توضیحات')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ ثبت')
                    ->formatStateUsing(fn ($state) => safe_jalali_datetime($state))
                    ->sortable(),
            ])
            ->filters([
                
            ])
            ->headerActions([
                
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
            ])
            ->emptyStateHeading('هیچ نظری ثبت نشده')
            ->emptyStateDescription('هنوز هیچ نظر و امتیازی برای این سفارش ثبت نشده است.')
            ->emptyStateIcon('heroicon-o-star')
            ->defaultSort('created_at', 'desc');
    }
}
