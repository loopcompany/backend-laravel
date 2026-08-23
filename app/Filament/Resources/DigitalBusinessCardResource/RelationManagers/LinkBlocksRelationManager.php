<?php

namespace App\Filament\Resources\DigitalBusinessCardResource\RelationManagers;

use App\Models\DigitalBusinessCardBlock;
use App\Models\LinkBlock;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LinkBlocksRelationManager extends RelationManager
{
    protected static string $relationship = 'link_blocks';
    protected static ?string $title = 'لینک ها';
    protected static ?string $modelLabel = 'لینک ها';
    protected static ?string $pluralModelLabel = 'لینک ها';
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
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->label('عنوان')
                    ->maxLength(191),
                Forms\Components\TextInput::make('link')
                    ->required()
                    ->label('لینک'),
                Forms\Components\ColorPicker::make('color')->required()->label('رنگ متن عنوان')

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
                Tables\Columns\TextColumn::make('link')->label('لینک'),
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

        return LinkBlock::query()
            ->select('link_blocks.*', 'digital_business_card_blocks.title as block_title')
            ->join(
                'digital_business_card_blocks',
                'digital_business_card_blocks.id',
                '=',
                'link_blocks.digital_business_card_block_id'
            )
            ->where('link_blocks.digital_business_card_id', $parent->id);
    }
}
