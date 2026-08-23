<?php

namespace App\Filament\Resources\CategoryResource\RelationManagers;

use App\Filament\Resources\CategoryResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Facades\Filament;

class CategoryRelationManager extends RelationManager
{

    protected static string $relationship = 'children';
    protected static ?string $title = 'مدیریت دسته‌بندی‌ها';
    protected static ?string $modelLabel = 'زیر دسته جدید';
    protected static ?string $pluralModelLabel  = 'دسته‌بندی‌ها';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('نام دسته‌بندی')
                    ->required()
                    ->maxLength(255),
                    
                Forms\Components\TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->helperText('لینک کوتاه و قابل خواندن برای URL. به انگلیسی باشد.'),

                Forms\Components\FileUpload::make('image_path')
                    ->label('تصویر دسته‌بندی')
                    ->directory('category')
                    ->image()
                    ->maxSize(2048) // حداکثر حجم 2MB
                    ->required(),

                Forms\Components\ToggleButtons::make('has_subcategory')
                    ->label('دارای زیرمجموعه؟')
                    ->options([
                        0 => '❌ ندارد',
                        1 => '✅ دارد',
                    ])
                    ->default(0)
                    ->inline()
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->reorderable('sort')
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('نام دسته‌بندی')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('parent.title')
                    ->label('دسته‌بندی والد')
                    ->sortable()
                    ->default('—'),

                Tables\Columns\ImageColumn::make('image_path')
                    ->label('تصویر')
                    ->size(50),

                Tables\Columns\BadgeColumn::make('has_subcategory')
                    ->label('نوع دسته‌بندی')
                    ->formatStateUsing(function ($state) {
                        return [
                            0 => 'دارای خدمت',
                            1 => 'دارای زیردسته',
                        ][$state] ?? 'ناشناس';
                    })
                    ->colors([
                        0 => 'primary',
                        1 => 'info',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ ایجاد')
                    ->sortable()
                    ->jalaliDate(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                ->modal(false) // غیرفعال کردن مودال
                ->url(fn ($record) => url('/admin/categories/' . $record->id . '/edit')),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function canViewForRecord(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->has_subcategory == 1;
    }

}
