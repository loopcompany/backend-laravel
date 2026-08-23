<?php

namespace App\Filament\Resources\CategoryResource\RelationManagers;

use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ApplicationSliderRelationManager extends RelationManager
{
    protected static string $relationship = 'children';
    protected static ?string $title = 'اسلایدر اپلیکیشن';
    protected static ?string $modelLabel = 'اسلایدر اپلیکیشن';
    protected static ?string $pluralModelLabel  = 'اسلایدر اپلیکیشن';
    
    protected function getTableQuery(): Builder
    {
        $ownerRecord = $this->getOwnerRecord(); // دریافت رکورد والد
        $ids = $ownerRecord->leafDescendants()->pluck('id');
        return Category::query()->whereIn('id', $ids);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->reorderable('sort2')
            ->columns([
                Tables\Columns\TextColumn::make('sort2')
                    ->label('ترتیب اسلایدر اپلیکیشن')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('id')
                    ->label('شناسه')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('نام دسته‌بندی')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\ImageColumn::make('image_path')
                    ->label('تصویر')
                    ->size(50),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }



    public function canCreate(): bool
    {
        return false;
    }
    public function canEdit($record): bool
    {
        return false;
    }

    public static function canViewForRecord(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->parent_id == null;
    }
}
