<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArchiveImageResource\Pages;
use App\Models\ArchiveImage;
use App\Models\Technician;
use App\Traits\HasFilamentPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ArchiveImageResource extends Resource
{
    use HasFilamentPermissions;
    
    protected static ?string $model = ArchiveImage::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';
    
    protected static ?string $navigationGroup = 'مدیریت تکنسین‌ها';
    
    protected static ?string $navigationLabel = 'آرشیو تصاویر';
    
    protected static ?string $modelLabel = 'تصویر آرشیو';
    
    protected static ?string $pluralModelLabel = 'آرشیو تصاویر';

    protected static function getViewPermission(): string
    {
        return 'view-archive-images';
    }

    protected static function getCreatePermission(): string
    {
        return 'create-archive-images';
    }

    protected static function getDeletePermission(): string
    {
        return 'delete-archive-images';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('اطلاعات تصویر')
                    ->schema([
                        Forms\Components\Select::make('technician_id')
                            ->label('تکنسین')
                            ->relationship('technician', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpanFull(),
                        
                        Forms\Components\FileUpload::make('image_path')
                            ->label('تصویر')
                            ->image()
                            ->directory('archive-images')
                            ->required()
                            ->imageEditor()
                            ->imageCropAspectRatio(null)
                            ->imageResizeTargetWidth('1920')
                            ->imageResizeTargetHeight('1920')
                            ->maxSize(5120)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('شناسه')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('technician.name')
                    ->label('نام تکنسین')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('technician.phone')
                    ->label('شماره تماس تکنسین')
                    ->searchable(),
                
                Tables\Columns\ImageColumn::make('image_path')
                    ->label('تصویر')
                    ->square()
                    ->size(60),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ ایجاد')
                    ->jalaliDateTime('Y/m/d H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('technician_id')
                    ->label('تکنسین')
                    ->relationship('technician', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('مشاهده'),
                Tables\Actions\DeleteAction::make()
                    ->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف'),
                ]),
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
            'index' => Pages\ListArchiveImages::route('/'),
            'create' => Pages\CreateArchiveImage::route('/create'),
            'view' => Pages\ViewArchiveImage::route('/{record}'),
        ];
    }
}
