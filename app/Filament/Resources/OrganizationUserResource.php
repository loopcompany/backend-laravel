<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrganizationUserResource\Pages;
use App\Models\OrganizationUser;
use App\Traits\HasFilamentPermissions;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * فهرست کاربران مجازی که هر سازمان از اپ ثبت کرده است (فقط مشاهده/حذف).
 */
class OrganizationUserResource extends Resource
{
    use HasFilamentPermissions;

    protected static ?string $model = OrganizationUser::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'مدیریت کاربران';

    protected static ?string $navigationLabel = 'کاربران مجاز سازمان‌ها';

    protected static ?string $modelLabel = 'کاربر مجاز سازمان';

    protected static ?string $pluralModelLabel = 'کاربران مجاز سازمان‌ها';

    protected static function getViewPermission(): string
    {
        return 'view-user';
    }

    protected static function getDeletePermission(): string
    {
        return 'edit-user';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('organization.organization_name')->label('سازمان/شرکت')->searchable(),
                Tables\Columns\TextColumn::make('full_name')->label('نام و نام خانوادگی')->searchable(),
                Tables\Columns\TextColumn::make('mobile')->label('موبایل')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('role')->label('سمت/نقش')->default('-'),
                Tables\Columns\TextColumn::make('created_at')->label('تاریخ ثبت')->jalaliDateTime('Y/m/d H:i')->sortable(),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrganizationUsers::route('/'),
        ];
    }
}
