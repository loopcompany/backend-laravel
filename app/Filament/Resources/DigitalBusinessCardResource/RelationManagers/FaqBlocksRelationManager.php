<?php

namespace App\Filament\Resources\DigitalBusinessCardResource\RelationManagers;

use App\Models\DigitalBusinessCardBlock;
use App\Models\FaqBlock;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FaqBlocksRelationManager extends RelationManager
{
    protected static string $relationship = 'faq_blocks';
    protected static ?string $title = 'سوالات متداول';
    protected static ?string $modelLabel = 'سوالات متداول';
    protected static ?string $pluralModelLabel = 'سوالات متداول';
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
                    ->label('سوال')
                    ->maxLength(191)
                    ->columnSpanFull()
                ,
                
                Forms\Components\ColorPicker::make('title_color')
                    ->required()
                    ->label('رنگ سوال')
                ,
                Forms\Components\ColorPicker::make('descriptions_color')
                    ->required()
                    ->label('رنگ جواب')
                ,

                Forms\Components\Textarea::make('descriptions')
                    ->required()
                    ->label('پاسخ')
                    ->columnSpanFull()
                ,
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->reorderable('sort')
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('شناسه'),
                Tables\Columns\TextColumn::make('block_title')->label('بلاک'),
                Tables\Columns\TextColumn::make('title')->label('عنوان'),
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

        return FaqBlock::query()
            ->select('faq_blocks.*', 'digital_business_card_blocks.title as block_title')
            ->join(
                'digital_business_card_blocks',
                'digital_business_card_blocks.id',
                '=',
                'faq_blocks.digital_business_card_block_id'
            )
            ->where('faq_blocks.digital_business_card_id', $parent->id);
    }

}
