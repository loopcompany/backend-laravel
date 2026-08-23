<?php

namespace App\Filament\Resources\DigitalBusinessCardResource\RelationManagers;

use App\Forms\Components\NeshanMapPicker;
use App\Models\DigitalBusinessCardBlock;
use App\Models\LinkBlock;
use App\Models\MapBlock;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MapBlocksRelationManager extends RelationManager
{
    protected static string $relationship = 'map_blocks';
    protected static ?string $title = 'مسیریاب';
    protected static ?string $modelLabel = 'مسیریاب';
    protected static ?string $pluralModelLabel = 'مسیریاب';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('digital_business_card_block_id')
                    ->options(function ($livewire) {
                        $parent = $livewire->getOwnerRecord();
            
                        return DigitalBusinessCardBlock::where('digital_business_card_id', $parent->id)
                            ->pluck('title', 'id');
                    })
                    ->label('بلاک مربوطه')
                    ->required(),
                Forms\Components\ColorPicker::make('color')->required()->label('رنگ متن'),

                Forms\Components\TextInput::make('title')
                    ->required()
                    ->label('عنوان'),
                Forms\Components\TextInput::make('address')
                    ->required()
                    ->label('آدرس'),

                NeshanMapPicker::make('map_location')
                    ->latitude('latitude')
                    ->longitude('longitude')
                    ->label('موقعیت روی نقشه'),
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
                Tables\Columns\TextColumn::make('title')->label('عنوان'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        \Log::info('CreateAction mutateFormDataUsing', ['data' => $data]);
                        
                        if (isset($data['map_location']) && is_array($data['map_location'])) {
                            $data['latitude'] = $data['map_location']['latitude'] ?? null;
                            $data['longitude'] = $data['map_location']['longitude'] ?? null;
                            unset($data['map_location']);
                        }

                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        \Log::info('EditAction mutateFormDataUsing', ['data' => $data]);
                        
                        if (isset($data['map_location']) && is_array($data['map_location'])) {
                            $data['latitude'] = $data['map_location']['latitude'] ?? null;
                            $data['longitude'] = $data['map_location']['longitude'] ?? null;
                            unset($data['map_location']);
                        }

                        return $data;
                    }),
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

        return MapBlock::query()
            ->select('map_blocks.*', 'digital_business_card_blocks.title as block_title')
            ->join(
                'digital_business_card_blocks',
                'digital_business_card_blocks.id',
                '=',
                'map_blocks.digital_business_card_block_id'
            )
            ->where('map_blocks.digital_business_card_id', $parent->id);
    }
}
