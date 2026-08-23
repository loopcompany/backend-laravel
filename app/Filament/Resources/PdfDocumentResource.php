<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PdfDocumentResource\Pages;
use App\Filament\Resources\PdfDocumentResource\RelationManagers;
use App\Models\PdfDocument;
use App\Traits\HasFilamentPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PdfDocumentResource extends Resource
{
    use HasFilamentPermissions;
    protected static ?string $model = PdfDocument::class;
    protected static ?string $navigationGroup = 'مدیریت محتوا';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'پی دی اف ها';
    protected static ?string $title = 'پی دی اف ها';
    protected static ?string $modelLabel = 'پی دی اف ها';
    protected static ?string $pluralModelLabel = 'پی دی اف ها';

    protected static function getViewPermission(): string
    {
        return 'view-pdf-document';
    }

    protected static function getEditPermission(): string
    {
        return 'edit-pdf-document';
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\FileUpload::make('user')
                    ->directory('pdf')
                    ->downloadable()
                    ->openable()
                    ->label('راهنمای کاربران'),
                Forms\Components\FileUpload::make('tech')
                    ->directory('pdf')->downloadable()
                    ->openable()
                    ->label('راهنمای تکنسین‌ها'),
                Forms\Components\FileUpload::make('organ')
                    ->directory('pdf')->downloadable()
                    ->openable()
                    ->label('راهنمای سازمان‌ها'),
                Forms\Components\FileUpload::make('organ_term')
                    ->directory('pdf')->downloadable()
                    ->openable()
                    ->label('قوانین و مقررات سازمانی'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('updated_at')
                    ->jalaliDateTime()
                    ->label('تاریخ بروزرسانی')
                ,
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
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
            'index' => Pages\ListPdfDocuments::route('/'),
            'create' => Pages\CreatePdfDocument::route('/create'),
            'edit' => Pages\EditPdfDocument::route('/{record}/edit'),
        ];
    }
}
