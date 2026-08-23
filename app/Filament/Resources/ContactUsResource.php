<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactUsResource\Pages;
use App\Filament\Resources\ContactUsResource\RelationManagers;
use App\Models\Access;
use App\Models\AdminAccess;
use App\Models\ContactUs;
use App\Traits\HasFilamentPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ContactUsResource extends Resource
{
    use HasFilamentPermissions;
    protected static ?string $model = ContactUs::class;

    protected static ?string $navigationGroup = 'مدیریت محتوا';
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-bottom-center';
    protected static ?int $navigationSort = 9;
    protected static ?string $navigationLabel = 'پیام های تماس با ما';
    protected static ?string $title = 'پیام های تماس با ما';
    protected static ?string $modelLabel = 'پیام های تماس با ما';
    protected static ?string $pluralModelLabel = 'پیام های تماس با ما';
    protected static function getViewPermission(): string
    {
        return 'view-contact-us';
    }

     

    protected static function getDeletePermission(): string
    {
        return 'delete-contact-us';
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(180)
                    ->label('نام')
                    ->columnSpan('full'),
                Forms\Components\TextInput::make('phone')
                    ->required()
                    ->maxLength(180)
                    ->label('شماره تماس')
                    ->columnSpan('full'),
                Forms\Components\TextInput::make('email')
                    ->required()
                    ->maxLength(180)
                    ->label('آدرس ایمیل')
                    ->columnSpan('full'),
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(180)
                    ->label('عنوان')
                    ->columnSpan('full'),
                Forms\Components\Textarea::make('message')
                    ->required()
                    ->columnSpanFull()
                    ->label('متن پیام'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->searchable()
                    ->label('شناسه'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->label('نام'),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->label('شماره تماس'),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->label('ایمیل'),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->label('موضوع'),
                Tables\Columns\ToggleColumn::make('status')
                    ->searchable()
                    ->label('وضعیت پاسخ'),
                Tables\Columns\TextColumn::make('created_at')
                    ->jalaliDate()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('تاریخ ثبت'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->sortable()
                    ->jalaliDate()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('تاریخ بروزرسانی'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        '0' => 'در انتظار بررسی',
                        '1' => 'پاسخ داده شده',
                    ])->label('وضعیت')
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListContactUs::route('/'),
            'create' => Pages\CreateContactUs::route('/create'),
            'edit' => Pages\EditContactUs::route('/{record}/edit'),
        ];
    }


    public static function canCreate(): bool
    {
        return false;
    }
    public static function canEdit($record): bool
    {
        return false;
    }

}
