<?php

namespace App\Filament\Resources\DigitalBusinessCardResource\RelationManagers;

use App\Models\DigitalBusinessCardBlock;
use App\Models\GalleryBlock;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class GalleryBlocksRelationManager extends RelationManager
{
    protected static string $relationship = 'gallery_blocks';
    protected static ?string $title = 'گالری';
    protected static ?string $modelLabel = 'گالری';
    protected static ?string $pluralModelLabel = 'گالری';
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('digital_business_card_block_id')
                    ->options(function ($livewire) {
                        $parent = $livewire->getOwnerRecord();   // رکورد DigitalBusinessCard
            
                        return DigitalBusinessCardBlock::where('digital_business_card_id', $parent->id)
                            ->pluck('title', 'id');
                    })
                    ->label('بلاک مربوطه')
                    ->columnSpanFull()
                    ->required(),
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->columnSpanFull()
                    ->label('عنوان')
                    ->maxLength(191),
                Forms\Components\Textarea::make('descriptions')
                    ->required()
                    ->columnSpanFull()
                    ->maxLength(191)
                    ->label('توضیحات'),
                Forms\Components\FileUpload::make('file_path')
                    ->required()
                    ->columnSpanFull()
                    ->label('تصویر'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->reorderable('sort')
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('عنوان'),
                Tables\Columns\TextColumn::make('block_title')->label('بلاک'),
                Tables\Columns\ImageColumn::make('file_path')->label('تصویر'),
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
    protected function getTableQuery(): Builder
    {
        $parent = $this->getOwnerRecord();

        return GalleryBlock::query()
            ->select('gallery_blocks.*', 'digital_business_card_blocks.title as block_title')
            ->join(
                'digital_business_card_blocks',
                'digital_business_card_blocks.id',
                '=',
                'gallery_blocks.digital_business_card_block_id'
            )
            ->where('gallery_blocks.digital_business_card_id', $parent->id);
    }
}
