<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use App\Filament\Resources\RoleResource\RelationManagers;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationGroup = 'مدیریت کاربران';

    protected static ?string $navigationLabel = 'سمت‌ها';

    protected static ?string $modelLabel = 'سمت';

    protected static ?string $pluralModelLabel = 'سمت‌ها';

    protected static function getViewPermission(): string { return 'view-roles'; }
    protected static function getCreatePermission(): string { return 'create-roles'; }
    protected static function getEditPermission(): string { return 'edit-roles'; }
    protected static function getDeletePermission(): string { return 'delete-roles'; }
    public static function canViewAny(): bool { return \App\Services\AdminDelegation::can('view-roles'); }
    public static function canCreate(): bool { return \App\Services\AdminDelegation::can('create-roles'); }
    public static function canEdit($record): bool { return \App\Services\AdminDelegation::can('edit-roles') && \App\Services\AdminDelegation::editable($record); }
    public static function canDelete($record): bool { return \App\Services\AdminDelegation::can('delete-roles') && \App\Services\AdminDelegation::editable($record) && $record->name!=='super-admin'; }
    public static function canDeleteAny(): bool { return false; }
    public static function shouldRegisterNavigation(): bool { return static::canViewAny(); }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder { return parent::getEloquentQuery()->where('guard_name','admin'); }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('اطلاعات سمت')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('نام سمت')
                            ->rule(fn()=>function ($attribute,$value,$fail) { if ($value==='super-admin' && !\App\Services\AdminDelegation::root()) $fail('سمت سوپرادمین محفوظ است.'); })
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\Select::make('guard_name')
                            ->label('نوع کاربری')
                            ->options(['admin'=>'ادمین'])->disabled()->dehydrated()
                            ->default('admin')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('مجوزها')
                    ->schema([
                        Forms\Components\CheckboxList::make('permissions')
                            ->label('انتخاب مجوزها')
                            ->relationship('permissions', 'name')
                            ->options(fn()=>Permission::whereIn('id',\App\Services\AdminDelegation::permissionIds())->get()->mapWithKeys(fn($permission)=>[$permission->id=>$permission->fa_name ?: $permission->name]))
                            ->disabled(fn()=>!\App\Services\AdminDelegation::can('assign-permissions'))
                            ->rule(fn()=>function ($attribute,$value,$fail) { if (\App\Services\AdminDelegation::can('assign-permissions') && !\App\Services\AdminDelegation::checkIds((array)$value,\App\Services\AdminDelegation::permissionIds())) $fail('واگذاری مجوز خارج از اختیارات شما مجاز نیست.'); })
                            ->searchable()
                            ->bulkToggleable()
                            ->columns(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('نام سمت')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->colors([
                        'danger' => 'super-admin',
                        'warning' => 'admin',
                        'primary' => 'moderator',
                    ]),
                Tables\Columns\TextColumn::make('guard_name')
                    ->label('نوع کاربری')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('permissions_count')
                    ->label('تعداد مجوزها')
                    ->counts('permissions')
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('users_count')
                    ->label('تعداد کاربران')
                    ->counts('users')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ ایجاد')
                    ->dateTime('Y/m/d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('guard_name')
                    ->label('نوع کاربری')
                    ->options([
                        'admin' => 'Admin',
                        'web' => 'Web',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('ویرایش'),
                Tables\Actions\DeleteAction::make()
                    ->label('حذف')
                    ->visible(fn($record) => $record->name !== 'super-admin'),
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
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
