<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MapRadiusResource\Pages;
use App\Filament\Resources\MapRadiusResource\RelationManagers;
use App\Models\MapRadius;
use App\Forms\Components\DistrictPicker;
use App\Traits\HasFilamentPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MapRadiusResource extends Resource
{
    use HasFilamentPermissions;
    protected static ?string $model = MapRadius::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $modelLabel = 'محدوده سرویس';

    protected static ?string $pluralModelLabel = 'محدوده سرویس';

    protected static ?string $navigationLabel = 'محدوده سرویس (مناطق)';

    protected static ?string $navigationGroup = 'مدیریت مکان';

    protected static ?int $navigationSort = 4;

    /**
     * There is only ever one service area, so the sidebar opens its map directly
     * instead of a one-row list the admin has to click through.
     */
    public static function getNavigationUrl(): string
    {
        return static::getUrl('edit', ['record' => MapRadius::current()]);
    }

    public static function canCreate(): bool
    {
        return false;
    }
    protected static function getViewPermission(): string
    {
        return 'view-map';
    }

    protected static function getCreatePermission(): string
    {
        return false;
    }

    protected static function getEditPermission(): string
    {
        return 'edit-map';
    }

    protected static function getDeletePermission(): string
    {
        return false;
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                DistrictPicker::make('regions')
                    ->label('مناطق تحت پوشش')
                    ->helperText('مناطقی که سرویس در آن‌ها ارائه می‌شود را روی نقشه انتخاب کنید. مناطق ۲۱ و ۲۲ به دو بخش شرق و غرب تقسیم شده‌اند و هر بخش جداگانه انتخاب می‌شود.')
                    ->columnSpanFull()
                    ->rule('array')
                    // Filled by EditMapRadius::mutateFormDataBeforeFill() and written
                    // back by its afterSave(); the selection lives on a pivot table.
                    ->dehydrated(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('regions_count')
                    ->label('تعداد مناطق')
                    ->counts('regions')
                    ->badge(),
                Tables\Columns\TextColumn::make('regions.title')
                    ->label('مناطق تحت پوشش')
                    ->badge()
                    ->limitList(5)
                    ->expandableLimitedList()
                    ->placeholder('انتخاب نشده'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('آخرین بروزرسانی')
                    ->dateTime('Y/m/d H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                // Removed delete bulk action
            ]);
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
            'index' => Pages\ListMapRadii::route('/'),
            'edit' => Pages\EditMapRadius::route('/{record}/edit'),
        ];
    }
}
