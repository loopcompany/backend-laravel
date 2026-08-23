<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrganizationTermResource\RelationManagers;
use App\Filament\Resources\OrganizationTermResource\Pages;
use App\Models\OrganizationTerm;
use App\Traits\HasFilamentPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrganizationTermResource extends Resource
{
    use HasFilamentPermissions;

    protected static ?string $model = OrganizationTerm::class;

    protected static ?string $navigationGroup = 'مدیریت محتوا';
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?int $navigationSort = 12;
    protected static ?string $navigationLabel = 'قوانین و مقررات سازمانی';
    protected static ?string $title = 'قوانین و مقررات سازمانی';
    protected static ?string $modelLabel = 'قوانین و مقررات سازمانی';
    protected static ?string $pluralModelLabel  = 'قوانین و مقررات سازمانی';

    protected static function getViewPermission(): string
    {
        return 'view-organization-terms';
    }

    protected static function getCreatePermission(): string
    {
        return 'create-organization-terms';
    }

    protected static function getEditPermission(): string
    {
        return 'edit-organization-terms';
    }

    protected static function getDeletePermission(): string
    {
        return 'delete-organization-terms';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(180)
                    ->label('عنوان')
                    ->columnSpan('full'),
                Forms\Components\Textarea::make('description')
                    ->required()
                    ->columnSpanFull()
                    ->rows(10)
                    ->label('توضیحات'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->label('عنوان'),
                Tables\Columns\TextColumn::make('description')
                    ->searchable()
                    ->words(10)
                    ->html()
                    ->label('توضیحات'),
                Tables\Columns\TextColumn::make('created_at')
                    ->jalaliDate()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('تاریخ ثبت'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->sortable()
                    ->jalaliDate()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('تاریخ ویرایش'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListOrganizationTerms::route('/'),
            'create' => Pages\CreateOrganizationTerm::route('/create'),
            'edit' => Pages\EditOrganizationTerm::route('/{record}/edit'),
        ];
    }
}
