<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdminAuthLogResource\Pages;
use App\Filament\Resources\AdminAuthLogResource\RelationManagers;
use App\Models\AdminAuthLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AdminAuthLogResource extends Resource
{
    protected static ?string $navigationGroup = 'گزارشات و آمار';

    protected static ?string $model = AdminAuthLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-right-on-rectangle';
    protected static ?int $navigationSort = 10;
    protected static ?string $navigationLabel = 'فعالیت‌های ورود/خروج مدیران';
    protected static ?string $modelLabel = 'فعالیت ورود/خروج مدیران';
    protected static ?string $pluralModelLabel = 'فعالیت‌های ورود/خروج مدیران';

    protected static function getViewPermission(): string
    {
        return 'view-admin-log';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->columns([
                Tables\Columns\TextColumn::make('admin.name')
                    ->label('ادمین')
                    ->searchable(),

                Tables\Columns\TextColumn::make('event')
                    ->label('رویداد')
                    ->badge()
                    ->colors([
                        'success' => 'login',
                        'danger' => 'logout',
                        'warning' => 'failed_login',
                    ]),

                Tables\Columns\TextColumn::make('ip')
                    ->label('IP'),

                Tables\Columns\TextColumn::make('logged_at')
                    ->label('زمان')
                    ->dateTime(),

                Tables\Columns\TextColumn::make('user_agent')
                    ->label('مرورگر')
                    ->limit(50),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListAdminAuthLogs::route('/'),
            'create' => Pages\CreateAdminAuthLog::route('/create'),
            'edit' => Pages\EditAdminAuthLog::route('/{record}/edit'),
        ];
    }
}
