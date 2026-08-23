<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdminResource\Pages;
use App\Filament\Resources\AdminResource\RelationManagers;
use App\Models\Admin;
use App\Traits\HasFilamentPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class AdminResource extends Resource
{
    protected static ?string $model = Admin::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    protected static ?string $navigationGroup = 'مدیریت کاربران';
    
    protected static ?string $navigationLabel = 'ادمین‌ها';
    
    protected static ?string $modelLabel = 'مدیر';
    
    protected static ?string $pluralModelLabel = 'ادمین‌ها';

    // ساده‌ترین کنترل دسترسی
    public static function canViewAny(): bool
    {
        return auth('admin')->user()?->can('manage-admins') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth('admin')->user()?->can('manage-admins') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth('admin')->user()?->can('manage-admins') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth('admin')->user()?->can('manage-admins') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('اطلاعات شخصی')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('نام')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('ایمیل')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\FileUpload::make('avatar')
                            ->label('تصویر پروفایل')
                            ->image()
                            ->directory('admins')
                            ->visibility('public'),
                    ])->columns(2),
                    
                Forms\Components\Section::make('رمز عبور')
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->label('رمز عبور')
                            ->password()
                            ->required(fn (string $context): bool => $context === 'create')
                            ->minLength(8)
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state)),
                    ])->columns(1),
                    
                Forms\Components\Section::make('دسترسی‌ها')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('فعال')
                            ->default(true),
                        Forms\Components\CheckboxList::make('roles')
                            ->label('سمت‌ها')
                            ->relationship('roles', 'name')
                            ->options(function () {
                                return Role::where('guard_name', 'admin')
                                    ->get()
                                    ->mapWithKeys(function ($role) {
                                        return [$role->id => $role->name];
                                    });
                            })
                            ->descriptions(function () {
                                return Role::where('guard_name', 'admin')
                                    ->get()
                                    ->mapWithKeys(function ($role) {
                                        $descriptions = [
                                            'super-admin' => 'دسترسی کامل به تمام بخش‌ها',
                                            'admin' => 'دسترسی به مدیریت کاربران و گزارشات',
                                            'moderator' => 'دسترسی محدود به مشاهده',
                                        ];
                                        return [$role->id => $descriptions[$role->name] ?? ''];
                                    });
                            })
                            ->columns(1),
                    ])->columns(2),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('تصویر')
                    ->circular()
                    ->size(40),
                Tables\Columns\TextColumn::make('name')
                    ->label('نام')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('ایمیل')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('سمت‌ها')
                    ->badge()
                    ->colors([
                        'danger' => 'super-admin',
                        'warning' => 'admin',
                        'primary' => 'moderator',
                    ]),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('وضعیت')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ ایجاد')
                    ->dateTime('Y/m/d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->label('نقش')
                    ->relationship('roles', 'name')
                    ->multiple(),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('وضعیت')
                    ->boolean()
                    ->trueLabel('فعال')
                    ->falseLabel('غیرفعال')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('ویرایش'),
                Tables\Actions\DeleteAction::make()
                    ->label('حذف')
                    ->hidden(fn (Admin $record): bool => $record->isSuperAdmin()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف انتخاب شده‌ها'),
                ]),
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
            'index' => Pages\ListAdmins::route('/'),
            'create' => Pages\CreateAdmin::route('/create'),
            'edit' => Pages\EditAdmin::route('/{record}/edit'),
        ];
    }
}
