<?php

namespace App\Filament\Resources\ExtraServiceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Category;

class CategoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'extra_service_categories';
    protected static ?string $title = 'مدیریت دسته بندی‌ها';
    protected static ?string $modelLabel = 'دسته بندی';
    protected static ?string $pluralModelLabel  = 'دسته بندی ها';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('category_id')
                ->label('دسته‌بندی')
                ->options(function ($get, $record) {
                    $query = \App\Models\Category::where('has_subcategory', 0);
                    
                    // حذف دسته‌بندی‌هایی که قبلاً اضافه شده‌اند
                    $ownerRecord = $this->getOwnerRecord();
                    if ($ownerRecord) {
                        $existingCategoryIds = $ownerRecord->categories()->pluck('categories.id')->toArray();
                        
                        // اگر در حالت ویرایش هستیم، دسته‌بندی فعلی را از لیست استثناء نگیریم
                        if ($record && isset($record->category_id)) {
                            $existingCategoryIds = array_diff($existingCategoryIds, [$record->category_id]);
                        }
                        
                        if (!empty($existingCategoryIds)) {
                            $query->whereNotIn('id', $existingCategoryIds);
                        }
                    }
                    
                    return $query->get()->mapWithKeys(function ($category) {
                        return [$category->id => $category->breadcrumb_title];
                    });
                })
                ->searchable()
                ->preload()
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                 Tables\Columns\TextColumn::make('category.title')
                    ->label('نام دسته‌بندی')
                    ->searchable(),
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
}
