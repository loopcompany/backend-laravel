<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactResource\Pages;
use App\Filament\Resources\ContactResource\RelationManagers;
use App\Models\Access;
use App\Models\AdminAccess;
use App\Models\Contact;
use App\Traits\HasFilamentPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ContactResource extends Resource
{
    use HasFilamentPermissions;

    protected static ?string $model = Contact::class;

    protected static ?string $navigationGroup = 'مدیریت محتوا';
    protected static ?string $navigationIcon = 'heroicon-o-phone';
    protected static ?int $navigationSort = 7;
    protected static ?string $navigationLabel = 'اطلاعات تماس';
    protected static ?string $title = 'اطلاعات تماس';
    protected static ?string $modelLabel = 'اطلاعات تماس';
    protected static ?string $pluralModelLabel = 'اطلاعات تماس';

    protected static function getViewPermission(): string
    {
        return 'view-contacts';
    }

    protected static function getCreatePermission(): string
    {
        return 'create-contacts';
    }

    protected static function getEditPermission(): string
    {
        return 'edit-contacts';
    }

    protected static function getDeletePermission(): string
    {
        return 'delete-contacts';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('اطلاعات تماس')->schema([
                    Forms\Components\Select::make('type')
                        ->required()
                        ->options([
                            'phone' => 'شماره تماس',
                            'office' => 'آدرس',
                            'email' => 'ایمیل',
                            'sms' => 'پیامک اطلاع رسانی مدیریتی(عدم نمایش به کاربران)',
                        ])
                        ->label('نوع'),
                    Forms\Components\TextInput::make('title')
                        ->required()
                        ->label('عنوان'),
                    Forms\Components\TextInput::make('link')
                        ->required()
                        ->label('لینک'),
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->label('مقدار'),
                    // Forms\Components\ToggleButtons::make('lang')
                    // ->options([
                    //     'fa' => 'فارسی',
                    //     'en' => 'انگلیسی'
                    // ])
                    // ->inline()
                    // ->default('fa')
                    // ->label('زبان'),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->searchable()
                    ->label('نوع'),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->label('عنوان'),
                Tables\Columns\TextColumn::make('link')
                    ->searchable()
                    ->label('لینک'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->label('مقدار'),
                Tables\Columns\TextColumn::make('created_at')
                    ->sortable()
                    ->jalaliDate()
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
                Tables\Actions\DeleteAction::make()->visible(fn($record) => $record->type !== 'urgent'),
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
            'index' => Pages\ListContacts::route('/'),
            'create' => Pages\CreateContact::route('/create'),
            'edit' => Pages\EditContact::route('/{record}/edit'),
        ];
    }


}
