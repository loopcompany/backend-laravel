<?php

namespace App\Filament\Resources\DigitalBusinessCardResource\RelationManagers;

use App\Models\DigitalBusinessCardBlock;
use App\Models\TextBlock;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TextBlocksRelationManager extends RelationManager
{
    protected static string $relationship = 'text_blocks';
    protected static ?string $title = 'بلاک های متنی';
    protected static ?string $modelLabel = 'بلاک های متنی';
    protected static ?string $pluralModelLabel = 'بلاک های متنی';
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
                    ->required()
                ,
                Forms\Components\Textarea::make('descriptions')
                    ->label('متن')
                    ->required()
                    ->columnSpanFull()
                ,
                Forms\Components\ColorPicker::make('color')->required()->label('رنگ متن')

            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->reorderable('sort')
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('شناسه'),
                Tables\Columns\TextColumn::make('block_title')->label('بلاک'),
                Tables\Columns\TextColumn::make('descriptions')->label('متن')->html()->words(10),
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

        return TextBlock::query()
            ->select('text_blocks.*', 'digital_business_card_blocks.title as block_title')
            ->join(
                'digital_business_card_blocks',
                'digital_business_card_blocks.id',
                '=',
                'text_blocks.digital_business_card_block_id'
            )
            ->where('text_blocks.digital_business_card_id', $parent->id);
    }
}
