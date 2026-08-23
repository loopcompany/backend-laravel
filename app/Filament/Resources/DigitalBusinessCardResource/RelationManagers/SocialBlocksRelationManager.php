<?php

namespace App\Filament\Resources\DigitalBusinessCardResource\RelationManagers;

use App\Models\DigitalBusinessCardBlock;
use App\Models\SocialBlock;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SocialBlocksRelationManager extends RelationManager
{
    protected static string $relationship = 'social_blocks';
    protected static ?string $title = 'راه‌های ارتباطی';
    protected static ?string $modelLabel = 'راه‌های ارتباطی';
    protected static ?string $pluralModelLabel = 'راه‌های ارتباطی';
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
                Forms\Components\FileUpload::make('icon')
                    ->required()
                    ->image()
                    ->label('آیکون'),
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->label('عنوان'),

                Forms\Components\TextInput::make('link')
                    ->required()
                    ->label('آدرس/آیدی/شماره'),
                Forms\Components\ColorPicker::make('color')->required()->label('رنگ متن عنوان')

            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->reorderable('sort')
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('عنوان'),
                Tables\Columns\TextColumn::make('block_title')->label('بلاک'),
                Tables\Columns\ImageColumn::make('icon')->label('آیکون'),

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

        return SocialBlock::query()
            ->select('social_blocks.*', 'digital_business_card_blocks.title as block_title')
            ->join(
                'digital_business_card_blocks',
                'digital_business_card_blocks.id',
                '=',
                'social_blocks.digital_business_card_block_id'
            )
            ->where('social_blocks.digital_business_card_id', $parent->id);
    }
}
