<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LetterRateResource\Pages;
use App\Filament\Resources\LetterRateResource\RelationManagers;
use App\Models\LetterRate;
use App\Models\LetterRateCategory;
use App\Traits\HasFilamentPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LetterRateResource extends Resource
{
    use HasFilamentPermissions;

    protected static ?string $model = LetterRate::class;

    protected static ?string $navigationGroup = 'مدیریت محتوا';
    protected static ?string $navigationIcon = 'heroicon-o-envelope';
    protected static ?int $navigationSort = 12;
    protected static ?string $navigationLabel = 'نرخ نامه‌ها';
    protected static ?string $title = 'نرخ نامه‌ها';
    protected static ?string $modelLabel = 'نرخ نامه';
    protected static ?string $pluralModelLabel = 'نرخ نامه‌ها';

    protected static function getViewPermission(): string
    {
        return 'view-letter-rates';
    }

    protected static function getCreatePermission(): string
    {
        return 'create-letter-rates';
    }

    protected static function getEditPermission(): string
    {
        return 'edit-letter-rates';
    }

    protected static function getDeletePermission(): string
    {
        return 'delete-letter-rates';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('اطلاعات نرخ نامه')->schema([
                    Forms\Components\Select::make('letter_rate_category_id')
                        ->label('دسته‌بندی')
                        ->options(LetterRateCategory::pluck('title', 'id'))
                        ->searchable()
                        ->preload()
                        ->placeholder('دسته‌بندی را انتخاب کنید'),
                    
                    Forms\Components\Select::make('type')
                        ->required()
                        ->options([
                            'union' => 'اتحادیه (Union)',
                            'loop' => 'لوپ (Loop)',
                        ])
                        ->label('نوع نرخ')
                        ->placeholder('نوع نرخ را انتخاب کنید'),
                    
                    Forms\Components\TextInput::make('title')
                        ->required()
                        ->maxLength(255)
                        ->label('عنوان نرخ')
                        ->placeholder('مثال: نامه عادی، ارسال سریع، ...')
                        ->columnSpanFull(),
                    
                    Forms\Components\TextInput::make('amount')
                        ->required()
                        ->maxLength(255)
                        ->label('مبلغ')
                        ->placeholder('مثال: 5,000 تومان')
                        ->helperText('مبلغ را با واحد پولی وارد کنید')
                        ->columnSpanFull(),
                ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('category.title')
                    ->label('دسته‌بندی')
                    ->sortable()
                    ->searchable()
                    ->placeholder('بدون دسته‌بندی'),
                
                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'primary' => 'union',
                        'success' => 'loop',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'union' => 'اتحادیه',
                        'loop' => 'لوپ',
                        default => $state,
                    })
                    ->label('نوع'),
                
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->label('عنوان نرخ'),
                
                Tables\Columns\TextColumn::make('amount')
                    ->searchable()
                    ->label('مبلغ'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->jalaliDate()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('تاریخ ثبت'),
                
                Tables\Columns\TextColumn::make('updated_at')
                    ->jalaliDate()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('تاریخ ویرایش'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'union' => 'اتحادیه',
                        'loop' => 'لوپ',
                    ])
                    ->label('نوع نرخ'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLetterRates::route('/'),
            'create' => Pages\CreateLetterRate::route('/create'),
            'edit' => Pages\EditLetterRate::route('/{record}/edit'),
        ];
    }
}
